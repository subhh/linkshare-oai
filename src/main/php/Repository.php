<?php

namespace SubHH\Linkshare\OAI;

use HAB\OAI\PMH\Model;
use HAB\OAI\PMH\ProtocolError;
use HAB\OAI\PMH\Repository\RepositoryInterface;

use Pimple\Container;

final class Repository implements RepositoryInterface
{
    /**
     * @var Container
     */
    private $commands;

    public function __construct (Container $commands)
    {
        $this->commands = $commands;
    }

    public function getRecord ($identifier, $metadataPrefix) : Model\ResponseBodyInterface
    {
        $cmd = $this->commands['GetRecord'];
        $cmd->identifier = $identifier;
        $cmd->metadataPrefix = $metadataPrefix;
        return $cmd->execute();
    }

    public function identify () : Model\Identity
    {
        return $this->commands['Identify']->execute();
    }

    public function listIdentifiers ($metadataPrefix, $from = null, $until = null, $set = null) : Model\ResponseBodyInterface
    {
        $cmd = $this->commands['ListIdentifiers'];
        $cmd->set = $set;
        $cmd->from = $from;
        $cmd->until = $until;
        $cmd->metadataPrefix = $metadataPrefix;

        return $cmd->execute();
    }

    public function listRecords ($metadataPrefix, $from = null, $until = null, $set = null) : Model\ResponseBodyInterface
    {
        $cmd = $this->commands['ListRecords'];
        $cmd->set = $set;
        $cmd->from = $from;
        $cmd->until = $until;
        $cmd->metadataPrefix = $metadataPrefix;

        return $cmd->execute();
    }

    public function listMetadataFormats ($identifier = null) : Model\ResponseBodyInterface
    {
        return $this->commands['ListMetadataFormats']->execute();
    }

    public function listSets () : Model\ResponseBodyInterface
    {
        return $this->commands['ListSets']->execute();
    }

    public function resume ($verb, $resumptionToken) : Model\ResponseBodyInterface
    {
        $cmd = $this->commands[$verb];
        $cmd->resume($resumptionToken);
        try {
            return $cmd->execute();
        } catch (ProtocolError\ProtocolError $error) {
            throw new ProtocolError\BadResumptionToken();
        }

    }

}
