<?php

declare(strict_types=1);

namespace Tests\Unit\Models\External;

use App\Interfaces\Repository\DocumentRepository;
use App\Models\External\DocumentModel;
use Tests\TestCase;

class DocumentModelTest extends TestCase
{
    public function test_it_returns_proper_repository_class(): void
    {
        self::assertEquals(DocumentRepository::class, DocumentModel::getRepositoryClass());
    }
}
