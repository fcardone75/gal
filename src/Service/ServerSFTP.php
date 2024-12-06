<?php
namespace App\Service;

use ErrorException;
use phpseclib3\Net\SFTP;

/**
 * ServerSFTP Class
 *
 * The `ServerSFTP` class is an extension of the `phpseclib3\Net\SFTP` class,
 * designed to enhance the functionality of SFTP operations within PHP applications.
 * It provides a more intuitive and flexible interface for managing SFTP connections,
 * including advanced file manipulation, error handling, and logging capabilities.
 * This class simplifies the process of interacting with SFTP servers by abstracting
 * complex operations and offering additional utilities for common tasks
 * such as file uploads, downloads, and directory listings.
 */
class ServerSFTP extends SFTP
{

    /**
     * @var string $sftpUsername
     */
    private $sftpUsername;

    /**
     * @var string $sftpPassword
     */
    private $sftpPassword;

    /**
     * ServerSFTP constructor.
     * @param $sftpHost
     * @param $sftpUsername
     * @param $sftpPassword
     */
    public function __construct($sftpHost, $sftpUsername, $sftpPassword)
    {
        $this->setSftpUsername($sftpUsername)
            ->setSftpPassword($sftpPassword);
        parent::__construct($sftpHost);
    }

    public function getSftpUsername(): string
    {
        return $this->sftpUsername;
    }

    protected function setSftpUsername(string $sftpUsername): ServerSFTP
    {
        $this->sftpUsername = $sftpUsername;
        return $this;
    }

    public function getSftpPassword(): string
    {
        return $this->sftpPassword;
    }

    protected function setSftpPassword(string $sftpPassword): ServerSFTP
    {
        $this->sftpPassword = $sftpPassword;
        return $this;
    }

    public function connectToServer(): bool
    {
        if (!$this->isConnected() && !$this->login($this->getSftpUsername(), $this->getSftpPassword())) {
            throw new ErrorException("SFTP LOGIN FAILED");
        }
        return true;
    }
}
