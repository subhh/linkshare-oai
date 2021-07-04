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

use PDOStatement;
use PDO;

/**
 * Serialize database record to DublinCore™.
 *
 * @author David Maus <david.maus@sub.uni-hamburg.de>
 * @copyright Copyright (c) 2021 by Staats- und Universitätsbibliothek Hamburg
 */
final class DublinCore implements Serializer
{
    /**
     * @var PDO
     */
    private $pdo;

    /**
     * @var string
     */
    private $descriptionQuery = <<<QUERY
SELECT
    quellendefinition.quellendefinition AS id ,
    quellenmerkmal.bezeichnung AS quellenmerkmal ,
    merkmalsnorm.bezeichnung AS merkmalsnorm ,
    quellendefinition.wert ,
    quellendefinition.sprache ,
    quellendefinition.elterndefinition
FROM
    quellendefinition
LEFT JOIN
    quellenmerkmal USING ( quellenmerkmal )
LEFT JOIN
    merkmalsnorm USING ( merkmalsnorm )
WHERE
    ( quellendefinition.gueltig IS NOT NULL OR quellenmerkmal.pflichtfeld IS NOT NULL )
    AND quellendefinition.geloescht IS NULL
    AND quellendefinition.quelle = :localIdentifier
ORDER BY
    quellenmerkmal.quellenmerkmal DESC ,
    quellendefinition.quellendefinition
QUERY;

    /**
     * @var PDOStatement
     */
    private $descriptionStatement;

    public function __construct (PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function serialize (int $localIdentifier) : string
    {
        $payload = array();
        $payload[] = '<oai_dc:dc xmlns:oai_dc="http://www.openarchives.org/OAI/2.0/oai_dc/" xmlns:dc="http://purl.org/dc/elements/1.1/">';

        $stmt = $this->getDescriptionStatement();
        $stmt->execute([':localIdentifier' => $localIdentifier]);
        $properties = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($properties as $property) {
            $value = $property['wert'];
            switch ($property['quellenmerkmal']) {
            case 'rechtliches':
                $payload[] = $this->createElementStr('dc:rights', $value);
                break;
            case 'person':
            case 'koerperschaft':
                $role = $property['merkmalsnorm'];
                if ($role === 'Urheber') {
                    $this->createElementStr('dc:creator', $value);
                } else if ($role === 'Verleger') {
                    $payload[] = $this->createElementStr('dc:publisher', $value);
                } else {
                    $value = sprintf('%s (%s)', $value, $role);
                    $payload[] = $this->createElementStr('dc:contributor', $value);
                }
                break;
            case 'titel':
                $payload[] = $this->createElementStr('dc:title', $value);
                break;
            case 'url':
                $payload[] = $this->createElementStr('dc:identifier', $value);
                break;
            case 'abstract':
                $payload[] = $this->createElementStr('dc:description', $value);
                break;
            case 'umfang':
                $payload[] = $this->createElementStr('dc:format', $value);
                break;
            case 'zeitabdeckung':
                $payload[] = $this->createElementStr('dc:coverage', $value);
                break;
            case 'publikationsdatum':
                $payload[] = $this->createElementStr('dc:date', $value);
                break;
            default:
                break;
            }
        }

        $payload[] = '</oai_dc:dc>';
        return implode(PHP_EOL, $payload);
    }

    private function createElementStr (string $name, string $content) : string
    {
        return sprintf('<%s>%s</%s>', $name, htmlentities($content, ENT_XML1), $name);
    }

    private function getDescriptionStatement ()
    {
        if ($this->descriptionStatement === null) {
            $this->descriptionStatement = $this->pdo->prepare($this->descriptionQuery);
        }
        return $this->descriptionStatement;
    }
}
