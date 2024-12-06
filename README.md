# Garanzia Artigianato Liguria

## Docker environment

### 1. Configure your environment
Copy .env.docker.sample in .env.docker
### 2. Start containers
Start containers
```
$ docker-compose --env-file ./.env.docker up -d
```

## MFA

Per abilitare/disabilitare la MFA durante il login, è necessario settare `MFA_ENABLED=0` rispettivamente a `0` o `1`.

In caso sia `0`, il servizio verrà disabilitato, in quanto nel file `scheb_2fa.yaml`, la condizione per cui il servizio è abilitato dipende dalla variabile di ambiente `enabled: '%env(MFA_ENABLED)%`.

Nel caso sia `1`, al contrario, la variabile d'ambiente setterà il servizio ad attivo e quindi la MFA sarà abilitata.

Nella `DashboardController`, quindi, non è più possibile injectare il servizio direttamente, altimenti se è disabilitato, Symfony lancia errore. Il controllo presente nel costruttore assicura che il servizio sia attivo, in questo modo si evita l'errore.

### Test MFA

Per testare il funzionamento manualmente (da browser), settare la env `MFA_ENABLED` a `true` o `false`. Dopo il login, aspettarsi la schermata di MFA nel caso sia `true`, altrimenti direttamente la dashboard.

Per testare il funzionamento con `phpunit`, settare la env `MFA_ENABLED` a `true` o `false`, lanciare `vendor/bin/phpunit` e aspettarsi che il test passi in ogni caso.

## Testing

Viene usato phpunit per i test.

Creare un file `phpunit.xml` copiandolo da `phpunit.xml.dist`.

Le env di test sono già presenti nel file `.env.test`, lanciare quindi `console doctrine:database:create --env=test` per creare il db di test e `console doctrine:schema:update --env=test --force`.

Lanciare i test con `vendor/bin/phpunit -vvv` per avere più info su eventuali errori
