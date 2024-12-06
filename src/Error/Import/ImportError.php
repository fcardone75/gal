<?php


namespace App\Error\Import;


class ImportError
{
    const CONTEXT_SPREADSHEET_FILE = 'file';
    const CONTEXT_SPREADSHEET_SHEET = 'sheet';
    const CONTEXT_SPREADSHEET_ROW = 'row';
    const CONTEXT_VALIDATION = 'validation';
    const CONTEXT_IMPORT = 'import';

    /** @var string */
    private $context;

    /** @var string */
    private $contextId;

    private $file;

    private $sheet;

    private $row;

    private $cell;

    /** @var string */
    private $error;

    /**
     * @var string
     */
    private $message;

    public function __construct(
        $context = null,
        $message = null,
        $contextId = null,
        $file = null,
        $sheet = null,
        $row = null,
        $cell = null,
        $error = null
    ) {
        $this->context = $context;
        $this->message = $message;
        $this->contextId = $contextId;
        $this->file = $file;
        $this->sheet = $sheet;
        $this->row = $row;
        $this->cell = $cell;
        $this->error = $error;
    }

    public static function create(): ImportError
    {
        return new self();
    }

    /**
     * @return string|null
     */
    public function getContext(): ?string
    {
        return $this->context;
    }

    /**
     * @param string $context
     */
    public function setContext($context): ImportError
    {
        $this->context = $context;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getContextId(): ?string
    {
        return $this->contextId;
    }

    /**
     * @param string $contextId
     * @return ImportError
     */
    public function setContextId($contextId)
    {
        $this->contextId = $contextId;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getMessage(): ?string
    {
        return $this->message;
    }

    /**
     * @param string $message
     * @return ImportError
     */
    public function setMessage($message)
    {
        $this->message = $message;
        return $this;
    }

    /**
     * @return mixed|null
     */
    public function getFile()
    {
        return $this->file;
    }

    /**
     * @param mixed|null $file
     * @return ImportError
     */
    public function setFile($file)
    {
        $this->file = $file;
        return $this;
    }

    /**
     * @return mixed|null
     */
    public function getSheet()
    {
        return $this->sheet;
    }

    /**
     * @param mixed|null $sheet
     * @return ImportError
     */
    public function setSheet($sheet)
    {
        $this->sheet = $sheet;
        return $this;
    }

    /**
     * @return mixed|null
     */
    public function getRow()
    {
        return $this->row;
    }

    /**
     * @param mixed|null $row
     * @return ImportError
     */
    public function setRow($row)
    {
        $this->row = $row;
        return $this;
    }

    /**
     * @return mixed|null
     */
    public function getCell()
    {
        return $this->cell;
    }

    /**
     * @param mixed|null $cell
     * @return ImportError
     */
    public function setCell($cell)
    {
        $this->cell = $cell;
        return $this;
    }

    /**
     * @return string
     */
    public function getError(): ?string
    {
        return $this->error;
    }

    /**
     * @param string $error
     * @return ImportError
     */
    public function setError(string $error): ImportError
    {
        $this->error = $error;
        return $this;
    }

}
