<?php

namespace App\Service;

use App\Service\Contracts\FiscalCodeValidatorInterface;

class FiscalCodeValidator implements FiscalCodeValidatorInterface
{
    /**
     * @var bool
     */
    private $valid = false;

    /**
     * @var string
     */
    private $gender = null;

    /**
     * @var integer
     */
    private $cityOfBirth = null;

    /**
     * @var integer
     */
    private $dayOfBirth = null;

    /**
     * @var integer
     */
    private $monthOfBirth = null;

    /**
     * @var integer
     */
    private $yearOfBirth = null;

    /**
     * @var string
     */
    private $error = null;

    /**
     * Lista sostituzioni per omocodia
     * @var array
     */
    private $listaDecOmocodia = [ 'A' => '!', 'B' => '!', 'C' => '!', 'D' => '!', 'E' => '!', 'F' => '!', 'G' => '!', 'H' => '!', 'I' => '!', 'J' => '!', 'K' => '!', 'L' => '0', 'M' => '1', 'N' => '2', 'O' => '!', 'P' => '3', 'Q' => '4', 'R' => '5', 'S' => '6', 'T' => '7', 'U' => '8', 'V' => '9', 'W' => '!', 'X' => '!', 'Y' => '!', 'Z' => '!' ];

    /**
     * Posizioni caratteri interessati ad alterazione di codifica in caso di omocodia
     * @var array
     */
    private $listaSostOmocodia = [ 6, 7, 9, 10, 12, 13, 14 ];

    /**
     * Lista peso caratteri PARI
     * @var array
     */
    private $listaCaratteriPari = [ '0' => 0, '1' => 1, '2' => 2, '3' => 3, '4' => 4, '5' => 5, '6' => 6, '7' => 7, '8' => 8, '9' => 9, 'A' => 0, 'B' => 1, 'C' => 2, 'D' => 3, 'E' => 4, 'F' => 5, 'G' => 6, 'H' => 7, 'I' => 8, 'J' => 9, 'K' => 10, 'L' => 11, 'M' => 12, 'N' => 13, 'O' => 14, 'P' => 15, 'Q' => 16, 'R' => 17, 'S' => 18, 'T' => 19, 'U' => 20, 'V' => 21, 'W' => 22, 'X' => 23, 'Y' => 24, 'Z' => 25 ];

    /**
     * Lista peso caratteri DISPARI
     * @var array
     */
    private $listaCaratteriDispari = [ '0' => 1 , '1' => 0 , '2' => 5 , '3' => 7 , '4' => 9 , '5' => 13, '6' => 15, '7' => 17, '8' => 19, '9' => 21, 'A' => 1 , 'B' => 0 , 'C' => 5 , 'D' => 7 , 'E' => 9 , 'F' => 13, 'G' => 15, 'H' => 17, 'I' => 19, 'J' => 21, 'K' => 2 , 'L' => 4 , 'M' => 18, 'N' => 20, 'O' => 11, 'P' => 3 , 'Q' => 6 , 'R' => 8 , 'S' => 12, 'T' => 14, 'U' => 16, 'V' => 10, 'W' => 22, 'X' => 25, 'Y' => 24, 'Z' => 23  ];

    /**
     * Lista calcolo codice CONTOLLO (carattere 16)
     * @var array
     */
    private $listaCodiceControllo = [ 0 => 'A',  1 => 'B',  2 => 'C',  3 => 'D',  4 => 'E',  5 => 'F',  6 => 'G',  7 => 'H',  8 => 'I',  9 => 'J', 10 => 'K', 11 => 'L', 12 => 'M', 13 => 'N', 14 => 'O', 15 => 'P', 16 => 'Q', 17 => 'R', 18 => 'S', 19 => 'T', 20 => 'U', 21 => 'V', 22 => 'W', 23 => 'X', 24 => 'Y', 25 => 'Z' ];

    /**
     *  Array per il calcolo del mese
     * @var array
     */
   private $listaDecMesi = [ 'A' => '01', 'B' => '02', 'C' => '03', 'D' => '04', 'E' => '05', 'H' => '06', 'L' => '07', 'M' => '08', 'P' => '09', 'R' => '10', 'S' => '11', 'T' => '12' ];

    /**
     * Lista messaggi di Errore
     * @var array
     */
    private $errorMap = [ 0 => 'Codice da analizzare assente', 1 => 'Lunghezza codice da analizzare non corretta', 2 => 'Il codice da analizzare contiene caratteri non corretti', 3 => 'Carattere non valido in decodifica omocodia', 4 => 'Codice fiscale non corretto' ];

    /**
     * Getter isValido
     * @return boolean
     */
    public function isValid()
    {
        return $this->valid;
    }

    /**
     * Getter Errore
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * Valida Codice Fiscale
     * @param string $code
     * @return boolean
     */
    public function validateCode(string $code): bool
    {
        $this->resetProperties();

        try
        {
            // Verifico che il Codice Fiscale sia valorizzato
            if ( empty($code) ) {
                $this->raiseException(0);
            }

            // Verifico che la lunghezza sia almeno di 16 caratteri
            if ( strlen($code) !== 16) {
                $this->raiseException(1);
            }

            // Controllo che la forma sia corretta
            if( !preg_match(self::CODE_PATTERN, $code) ) {
                $this->raiseException(2);
            }

            // Converto in maiuscolo
            $code = strtoupper($code);

            // Converto la stringa in array
            $CodiceFiscaleArray = str_split($code);


            // Verifica la correttezza delle alterazioni per omocodia
            for ($i = 0; $i < count($this->listaSostOmocodia); $i++)
            {
                if (!is_numeric($CodiceFiscaleArray[$this->listaSostOmocodia[$i]]))
                {
                    if ($this->listaDecOmocodia[$CodiceFiscaleArray[$this->listaSostOmocodia[$i]]]==='!')
                        $this->raiseException(3);
                }
            }

            $Pari    = 0;
            $Dispari = $this->listaCaratteriDispari[$CodiceFiscaleArray[14]];

            // Giro sui primi 14 elementi a passo due
            for ($i = 0; $i < 13; $i+=2)
            {
                $Dispari = $Dispari + $this->listaCaratteriDispari[$CodiceFiscaleArray[$i]];
                $Pari    = $Pari    + $this->listaCaratteriPari[$CodiceFiscaleArray[$i+1]];
            }

            // Verifica congruenza dei valori calcolati sui primi 15 caratteri, con il codice di controllo (carattere 16)
            if (!($this->listaCodiceControllo[($Pari+$Dispari) % 26]  === $CodiceFiscaleArray[15]))
                $this->raiseException(4);

            // Sostituzione per risolvere eventuali omocodie
            for ($i = 0; $i < count($this->listaSostOmocodia); $i++)
            {
                if (!is_numeric($CodiceFiscaleArray[$this->listaSostOmocodia[$i]]))
                    $CodiceFiscaleArray[$this->listaSostOmocodia[$i]] = $this->listaDecOmocodia[$CodiceFiscaleArray[$this->listaSostOmocodia[$i]]];
            }

            // Converto l'array in stringa
            $CodiceFiscaleAdattato = implode($CodiceFiscaleArray);

            // Estraggo i dati
            $this ->gender         = ((int)(substr($CodiceFiscaleAdattato,9,2) > 40) ? self::CHAR_FEMALE : self::CHAR_MALE);
            $this ->cityOfBirth = substr($CodiceFiscaleAdattato, 11, 4);
            $this ->yearOfBirth   = substr($CodiceFiscaleAdattato, 6,  2);
            $this ->dayOfBirth = substr($CodiceFiscaleAdattato, 9,  2);
            $this ->monthOfBirth   = $this->listaDecMesi[substr($CodiceFiscaleAdattato,8,1)];

            // Recupero giorno di nascita se Sesso=F
            if($this->gender == self::CHAR_FEMALE)
            {
                $this ->dayOfBirth = $this ->dayOfBirth - 40;

                if (strlen($this ->dayOfBirth)===1)
                    $this ->dayOfBirth = '0'.$this ->dayOfBirth;
            }

            // Controlli teminati
            $this ->valid = true;
            $this ->error   = null;
        }
        catch(\Exception $e)
        {
            $this->error   = $e->getMessage();
            $this->valid = false;
        }

        return $this->valid;
    }


    /**
     * Resetta le proprietà della classe
     * @return void
     */
    private function resetProperties()
    {
        $this->valid      = false;
        $this->gender         = null;
        $this->cityOfBirth = null;
        $this->dayOfBirth = null;
        $this->monthOfBirth   = null;
        $this->yearOfBirth   = null;
        $this->error        = null;
    }


    /**
     * @param int|string $errorCode
     * @return void
     *@throws \Exception
     */
    private function raiseException($errorCode)
    {
        $errorMessage = $this->errorMap[$errorCode] ?? 'Eccezione non gestita';

        throw new \Exception($errorMessage, $errorCode);
    }
}
