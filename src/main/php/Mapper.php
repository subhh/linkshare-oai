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

use PDO;
use PDOStatement;

use RuntimeException;
use UnexpectedValueException;

use HAB\OAI\PMH\Model\Set;
use HAB\OAI\PMH\Model\Header;
use HAB\OAI\PMH\Model\UtcDateTime;
use HAB\OAI\PMH\ProtocolError;

/**
 * Use the Mapper pattern to map database to domain objects.
 *
 * @author David Maus <david.maus@sub.uni-hamburg.de>
 * @copyright Copyright (c) 2021 by Staats- und Universitätsbibliothek Hamburg
 */
final class Mapper
{
    /**
     * Database handle.
     *
     * @var PDO
     */
    private $pdo;

    /**
     * @var string
     */
    private $kollektionQuery = <<<END
select kollektion, beschreibung from
  quelle inner join quelle_institution using (quelle)
         inner join kollektion_institution using (institution)
         inner join kollektion using (kollektion)
where
  quelle = :quelle and freigeschaltet is not null
END;

    /**
     * @var PDOStatement
     */
    private $kollektionStmt;

    /**
     * @var array<string, Serializer>
     */
    private $export = array();

    public function __construct (PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Return record metadata.
     *
     * @return string
     */
    public function getRecordMetadata (string $identifier, string $format) : string
    {
        $localIdentifier = LocalIdentifier::decode($identifier);
        if ($localIdentifier === null) {
            throw new ProtocolError\IdDoesNotExist();
        }
        if (!isset($this->export[$format])) {
            throw new ProtocolError\CannotDisseminateFormat();
        }
        return $this->export[$format]->serialize($localIdentifier);
    }

    /**
     * Return earliest datestamp.
     *
     * @return UtcDateTime
     */
    public function getEarliestDatestamp () : UtcDateTime
    {
        $stmt = $this->pdo->query('select min(geaendert) from quelle');
        if ($stmt === false) {
            throw new RuntimeException();
        }

        $date = $stmt->fetchColumn();
        return new UtcDateTime((string)$date);
    }

    /**
     * Return number of sets.
     *
     * @return int
     */
    public function countSets ()
    {
        $stmt = $this->pdo->prepare('select count(*) from kollektion');
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    /**
     * Return sets.
     *
     * @return Set[]
     */
    public function findSets (int $cursor, int $itemsPerPage) : array
    {
        $query = sprintf('select kollektion, beschreibung from kollektion limit %d, %d', $cursor, $itemsPerPage);
        $stmt = $this->pdo->prepare($query);
        $stmt->execute();

        $sets = array();
        $rows = $stmt->fetchAll();
        if (!is_array($rows)) {
            throw new UnexpectedValueException();
        }
        foreach ($rows as $row) {
            $sets[] = new Set($row['beschreibung'], $row['kollektion']);
        }
        return $sets;
    }

    /**
     * Return single record header.
     *
     * @return ?Header
     */
    public function getRecord (string $identifier) : ?Header
    {
        $localIdentifier = LocalIdentifier::decode($identifier);
        if ($localIdentifier === null) {
            throw new ProtocolError\IdDoesNotExist();
        }

        $stmt = $this->pdo->prepare('select quelle, geaendert from quelle where quelle = :quelle and freigeschaltet is not null');
        $stmt->execute([':quelle' => $localIdentifier]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (empty($row)) {
            throw new ProtocolError\IdDoesNotExist();
        }
        $identifier = LocalIdentifier::encode($row['quelle']);
        $datestamp = new UtcDateTime($row['geaendert']);

        $sets = array();
        foreach ($this->findSetsContaining($localIdentifier) as $set) {
            $sets[] = $set->getSpec();
        }

        return new Header($identifier, $datestamp, $sets);
    }

    /**
     * Return number of records matching criteria.
     *
     * @return int
     */
    public function countRecords (string $from = null, string $until = null, string $set = null) : int
    {
        $query = $this->createQueryConditions($from, $until, $set);
        $queryStr = sprintf(
            'select count(*) from quelle %s where %s',
            implode(' ', $query['join']),
            implode(' and ', $query['where'])
        );
        $stmt = $this->pdo->prepare($queryStr);
        $stmt->execute($query['parameters']);
        return (int)$stmt->fetchColumn();
    }

    /**
     * Return record headers.
     *
     * @return Header[]
     */
    public function findRecords (string $from = null, string $until = null, string $set = null, int $cursor = 0, int $itemsPerPage = 25) : array
    {
        $query = $this->createQueryConditions($from, $until, $set);
        $queryStr = sprintf(
            'select quelle, geaendert from quelle %s where %s limit %d, %d',
            implode(' ', $query['join']),
            implode(' and ', $query['where']),
            $cursor,
            $itemsPerPage
        );
        $stmt = $this->pdo->prepare($queryStr);
        $stmt->execute($query['parameters']);

        $records = array();
        $rows = $stmt->fetchAll();
        if (!is_array($rows)) {
            throw new UnexpectedValueException();
        }

        foreach ($rows as $row) {
            $identifier = LocalIdentifier::encode($row['quelle']);
            $datestamp = new UtcDateTime($row['geaendert']);

            $sets = array();
            foreach ($this->findSetsContaining((int)$row['quelle']) as $set) {
                $sets[] = $set->getSpec();
            }

            $records[] = new Header($identifier, $datestamp, $sets);
        }
        return $records;
    }

    /**
     * Add metadata serializer.
     *
     * @return void
     */
    public function addSerializer (string $format, Serializer $serializer) : void
    {
        $this->export[$format] = $serializer;
    }

    /**
     * Return query conditions.
     *
     * @return array<string, array>
     */
    private function createQueryConditions (string $from = null, string $until = null, string $set = null) : array
    {
        $query = [
            'parameters' => [],
            'where' => [],
            'join' => []
        ];

        if ($from) {
            $query['parameters'][':from'] = $from;
            $query['where'][] = 'geaendert >= :from';
        }
        if ($until) {
            $query['parameters'][':until'] = $until;
            $query['where'][] = 'geaendert <= :until';
        }
        $query['where'][] = 'freigeschaltet is not null';

        if ($set) {
            $query['join'][] = 'inner join quelle_institution using (quelle)';
            $query['join'][] = 'inner join kollektion_institution using (institution)';
            $query['where'][] = 'kollektion = :set';
            $query['parameters'][':set'] = $set;
        }

        return $query;
    }

    /**
     * Return sets containing a record.
     *
     * @return Set[]
     */
    private function findSetsContaining (int $localIdentifier) : iterable
    {
        if ($this->kollektionStmt === null) {
            $this->kollektionStmt = $this->pdo->prepare($this->kollektionQuery);
        }

        $sets = array();
        $this->kollektionStmt->execute([':quelle' => $localIdentifier]);

        $rows = $this->kollektionStmt->fetchAll();
        if (!is_array($rows)) {
            throw new UnexpectedValueException();
        }

        foreach ($rows as $row) {
            $sets[] = new Set($row['beschreibung'], $row['kollektion']);
        }
        return $sets;
    }
}
