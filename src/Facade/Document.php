<?php

declare(strict_types=1);

namespace RxMake\Facade;

use DocumentItem;
use DocumentModel;

class Document
{
    /**
     * Get DocumentItem instance by $documentSrl.
     * Alias of DocumentModel::getDocument.
     *
     * @param int $documentSrl
     *
     * @return DocumentItem
     */
    public static function getDocument(int $documentSrl): DocumentItem
    {
        return DocumentModel::getDocument($documentSrl);
    }

    public static function insertDocument(): DocumentItem
    {

    }
}
