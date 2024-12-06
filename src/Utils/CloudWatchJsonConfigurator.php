<?php

namespace App\Utils;

use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\AbstractProcessingHandler;

/**
 * Configure a given Monolog handler to format messages as JSON
 */
class CloudWatchJsonConfigurator
{
    /**
     * Format logs being sent to CloudWatch to JSON
     *
     * @param AbstractProcessingHandler $handler
     **/
    public function configure(AbstractProcessingHandler $handler)
    {
        $handler->setFormatter(new JsonFormatter());
    }
}