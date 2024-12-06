<?php

namespace App\Model\User;

use App\Entity\User;
use App\Service\Contracts\Security\RoleProviderInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Security\User\EntityUserProvider;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class CsvImporter
{
    /** @var RoleProviderInterface  */
    private $roleProvider;

    /** @var ValidatorInterface  */
    private $validator;

    /** @var TranslatorInterface  */
    private $translator;

    /** @var EntityManagerInterface */
    private $entityManager;

    /** @var PasswordHasherFactoryInterface */
    private $passwordHasherFactory;

    /** @var EntityUserProvider */
    private $userProvider;

    /** @var array  */
    private $map = [];

    /** @var array  */
    private $normalizationMap = [];

    /** @var array  */
    private $validationMap = [];

    /** @var array  */
    private $errors = [];

    /**
     * CsvImporter constructor.
     * @param RoleProviderInterface $roleProvider
     * @param ValidatorInterface $validator
     * @param TranslatorInterface $translator
     * @param EntityManagerInterface $entityManager
     * @param PasswordHasherFactoryInterface $passwordHasherFactory
     * @param EntityUserProvider $userProvider
     * @param array $map
     * @param array $normalizationMap
     * @param array $validationMap
     */
    public function __construct(
        RoleProviderInterface          $roleProvider,
        ValidatorInterface             $validator,
        TranslatorInterface            $translator,
        EntityManagerInterface         $entityManager,
        PasswordHasherFactoryInterface $passwordHasherFactory,
        EntityUserProvider             $userProvider,
        array                          $map = [],
        array                          $normalizationMap = [],
        array                          $validationMap = []
    ) {
        $this->roleProvider = $roleProvider;
        $this->validator = $validator;
        $this->translator = $translator;
        $this->entityManager = $entityManager;
        $this->passwordHasherFactory = $passwordHasherFactory;
        $this->userProvider = $userProvider;
        $this->map = $map;
        if ($normalizationMap) {
            $this->buildNormalizationMap($normalizationMap);
        }
        if ($validationMap) {
            $this->buildValidationMap($validationMap);
        }
    }

    public function getTemplateContent()
    {
        return implode("\n", [
            implode(',', $this->map),
            implode(',', [
                'name@example.com',
                '"' . implode('|', array_keys($this->roleProvider->getRolesListAsOptionArray())) . ' (uno o più ruoli tra quelli mostrati, separati da "|")"',
                'password',
                '"1 (1 => abilitato, 0 => disabilitato)"',
                'aaaa-mm-gg (data di scadenza nel formato indicato)'
            ])
        ]);
    }

    public function validateCsv($file)
    {
        $res = fopen($file, 'r');
        $i = 1;

        while ($line = fgetcsv($res)) {
            if ($i === 1 && !array_diff($this->map, $line)) {
                $i++;
                continue;
            }
            if ($lineErrors = $this->validateCsvLine($line)) {
                $this->errors[] = [
                    'line' => $i,
                    'errors' => $lineErrors
                ];
                continue;
            }

            $i++;
        }
    }

    public function importCsv($file): array
    {
        $res = fopen($file, 'r');
        $i = 1;
        $passwordHasher = $this->passwordHasherFactory->getPasswordHasher(User::class);
        $report = ['total' => 0, 'created' => 0, 'updated' => 0, 'errors' => 0];

        while ($line = fgetcsv($res)) {
            if ($i === 1 && !array_diff($this->map, $line)) {
                continue;
            }
            if ($lineErrors = $this->validateCsvLine($line)) {
                $this->errors[] = [
                    'line' => $i,
                    'errors' => $lineErrors
                ];
                continue;
            }
            $this->normalizeCsvLine($line);

            $user = new User();
            foreach ($this->map as $pos => $field) {
                $method = $this->setterForMappedField($field);
                if (method_exists(User::class, $method)) {
                    $user->{$method}($line[$pos]);
                }
            }

            try {
                $existingUser = $this->userProvider->loadUserByIdentifier($user->getUsername());
            } catch (UserNotFoundException $e) {
                $existingUser = null;
            }

            if ($existingUser) {
                $user = $existingUser;
                foreach ($this->map as $pos => $field) {
                    $method = $this->setterForMappedField($field);
                    if (method_exists(User::class, $method)) {
                        $user->{$method}($line[$pos]);
                    }
                }
            }

            // Validation should be performed here if needed

            if ($user->getPlainPassword()) {
                $user
                    ->setPasswordIsGenerated(true)
                    ->setPassword($passwordHasher->hash($user->getPlainPassword(), $user->getSalt()));
//                    ->eraseCredentials(); commented to get plainPassword on send UserCreated Email
            }

            try {
                $created = 0;
                $updated = 1;
                if (!$user->getId()) {
                    $created = $updated--;
                    $this->entityManager->persist($user);
                }

                $cs = $this->entityManager->getUnitOfWork()->getEntityChangeSet($user);

                if ($cs || !$existingUser) {
                    $this->entityManager->flush();
                }
            } catch (\Exception $e) {
                // TODO: log exception
                $report['errors'] += 1;
                $created = $updated = 0;
            }

            $report['created'] += $created;
            $report['updated'] += $updated;
            $report['total'] += 1;

        }

        return $report;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    protected function validateCsvLine($line): array
    {
        $errors = [];
        if (count($line) !== count($this->map)) {
            $errors[] = [
                'message' => $this->translator->trans('crud.users.csv_import.errors.columns_count', [
                    'expected' => count($this->map),
                    'actual' => count($line)
                ])
            ];
        }
        foreach ($line as $key => $value) {
            if (isset($this->map[$key]) && isset($this->validationMap[$this->map[$key]])) {
                $violations = $this->validator->validate($value, $this->validationMap[$this->map[$key]]);
                if ($violations->count() > 0) {
                    foreach ($violations as $violation) {
                        $errors[] = [
                            'field' => $this->map[$key],
                            'message' => $violation->getMessage(),
                            'invalid_value' => $violation->getInvalidValue()
                        ];
                    }
                }
            }
        }
        return $errors;
    }

    protected function normalizeCsvLine(array &$line = [])
    {
        foreach ($line as $key => $value) {
            if (isset($this->normalizationMap[$this->map[$key]])) {
                $line[$key] = $this->normalizationMap[$this->map[$key]]($value);
            }
        }
    }

    public function buildNormalizationMap($normalizationMap)
    {
        foreach ($normalizationMap as $field => $config) {
            if (is_string($config)) {
                $callback = $config;
            } else {
                $callback = is_array($config) && isset($config['callback']) ? $config['type'] : null;
            }

            if ($callback && method_exists($this, $callback)) {
                $this->normalizationMap[$field] = function ($value) use ($callback) {
                    return call_user_func([$this, $callback], $value);
                };
            }
        }
    }

    protected function buildValidationMap(array $validationMapConfig)
    {
        foreach ($validationMapConfig as $key => $config) {
            if (!is_array($config)) {
                $this->validationMap[$key] = new $config();
            }
            if (is_array($config)) {
                foreach ($config as $constraint => $constraintOptions) {
                    $this->validationMap[$key][] = new $constraint($constraintOptions);
                }
            }
        }
    }

    protected function normalizeRoles($roles)
    {
        $roles = explode('|', $roles);
        $configuredRoles = $this->roleProvider->getRolesListAsOptionArray();
        $normalizedRoles = [];

        foreach ($roles as $role) {
            if (isset($configuredRoles[$role])) {
                $normalizedRoles[] = $configuredRoles[$role];
            }
        }

        return $normalizedRoles;
    }

    protected function normalizeExpiresAt($expiresAt)
    {
        return $expiresAt ? \DateTime::createFromFormat('Y-m-d', $expiresAt) : null;
    }

    protected function setterForMappedField($field): string
    {
        return 'set' . ucfirst(preg_replace_callback(
            '/_([^_])/',
            function (array $m) {
                return ucfirst($m[1]);
            },
            $field
        ));
    }

}
