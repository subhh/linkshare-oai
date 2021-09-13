<?php

declare(strict_types=1);

/**
 * This file is part of Linkshare OAI Webservice.
 *
 * Linkshare OAI Webservice is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Linkshare OAI Webservice is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Linkshare OAI Webservice.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace SubHH\Linkshare\OAI;

use HAB\OAI\PMH\ProtocolError;
use HAB\OAI\PMH\Model\ResponseBody;
use HAB\OAI\PMH\Model\ResponseBodyInterface;
use HAB\OAI\PMH\Model\Metadata;
use HAB\OAI\PMH\Model\Record;
use HAB\OAI\PMH\Model\Set;

/**
 * OAI PMH ListRecords operation.
 *
 * @author David Maus <david.maus@sub.uni-hamburg.de>
 * @copyright Copyright (c) 2021 by Staats- und Universitätsbibliothek Hamburg
 */
final class ListRecords extends Command
{
    use Resumable;

    /**
     * @var string
     */
    public $from;

    /**
     * @var string
     */
    public $until;

    /**
     * @var string
     */
    public $set;

    /**
     * @var string
     */
    public $metadataPrefix;

    public function execute () : ResponseBodyInterface
    {
        $completeListSize = $this->mapper->countRecords($this->from, $this->until, $this->set);
        $records = $this->mapper->findRecords($this->from, $this->until, $this->set, $this->cursor, $this->itemsPerPage);
        if (empty($records)) {
            throw new ProtocolError\NoRecordsMatch();
        }
        $cursor = $this->cursor + count($records);

        $response = new ResponseBody();
        foreach ($records as $record) {
            $metadata = $this->mapper->getRecordMetadata($record->getIdentifier(), $this->metadataPrefix);
            $response->append(new Record($record, new Metadata($metadata)));
        }

        $resumptionToken = $this->createResumptionToken($cursor, $completeListSize);
        if ($resumptionToken) {
            $response->setResumptionToken($resumptionToken);
        }

        return $response;
    }
}
