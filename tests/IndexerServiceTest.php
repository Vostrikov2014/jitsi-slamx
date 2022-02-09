<?php

namespace App\Tests;

use App\Repository\UserRepository;
use App\Service\IndexUser;
use App\Service\IndexUserService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class IndexerServiceTest extends KernelTestCase
{
    public function testIndexer(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $indexer = self::getContainer()->get(IndexUserService::class);
        $userRepo = self::getContainer()->get(UserRepository::class);
        $user = $userRepo->findOneBy(array('username'=>'test@local.de'));
        $index = $indexer->indexUser($user);
        self::assertEquals($user->getIndexer(), $index);
        self::assertNull($indexer->indexUser(null));
    }
}
