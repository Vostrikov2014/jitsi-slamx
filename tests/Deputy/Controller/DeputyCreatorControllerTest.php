<?php

namespace App\Tests\Deputy\Controller;

use App\Entity\User;
use App\Repository\RoomsRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockFileSessionStorage;

class DeputyCreatorControllerTest extends WebTestCase
{
    private $client;
    private User $manager;
    private User $deputy;
    private EntityManagerInterface $em;
    private $session;
    public function setUp(): void
    {

        parent::setUp(); // TODO: Change the autogenerated stub
        $this->session = new Session(new MockFileSessionStorage());
        $this->session->start();
        $this->client = static::createClient();
        $userRepo = self::getContainer()->get(UserRepository::class);
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->deputy = $userRepo->findOneBy(['email' => 'test@local.de']);
        $this->manager = $userRepo->findOneBy(['email' => 'test@local2.de']);
        $this->manager->addAddressbook($this->deputy);
        $this->em->persist($this->manager);
        $this->em->flush();

        $this->client->loginUser($this->manager);
        $this->client->request('GET', '/room/deputy/toggle/' . $this->deputy->getUid());
        $crawler = $this->client->request('GET', '/room/dashboard');
        self::assertEquals(0, $crawler->filter('.createdFromText')->count());
        self::assertEquals(0, $crawler->filter('.createdByDeputy')->count());
        $this->client->loginUser($this->deputy);
    }

    public function testCreateConference(): void
    {

        $userRepo = self::getContainer()->get(UserRepository::class);
        $deputy = $userRepo->findOneBy(['email' => 'test@local.de']);
        $manager = $userRepo->findOneBy(['email' => 'test@local2.de']);


        $server = $this->deputy->getServers()->toArray()[0];

        $crawler = $this->client->request('GET', '/room/new');
        $buttonCrawlerNode = $crawler->selectButton('Speichern');
        $form = $buttonCrawlerNode->form();
        $form['room[server]'] = $server->getId();
        $form['room[moderator]'] = $this->manager->getId();
        $form['room[name]'] = 'test for the supervisor';
        $form['room[start]'] = (new \DateTime())->format('Y-m-d H:i:s');
        $form['room[duration]'] = "60";

        $this->client->submit($form);
        $flash = $this->session->getBag('flashes')->all();


        $crawler = $this->client->request('GET', '/room/dashboard');
        self::assertResponseIsSuccessful();
        $flashMessage = $crawler->filter('.snackbar')->text();
        self::assertEquals($flashMessage, 'Die Konferenz wurde erfolgreich erstellt.');
        self::assertEquals(1, $crawler->filter('.createdFromText')->count());
        self::assertEquals('Erstellt von: Test1, 1234, User, Test', $crawler->filter('.createdFromText')->text());
        self::assertEquals(1, $crawler->filter('.createdByDeputy')->count());
        self::assertEquals(0, $crawler->filter('.createdByDeputy.loadContent')->count());
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'test for the supervisor']);

        $userRepo = self::getContainer()->get(UserRepository::class);
        $deputy = $userRepo->findOneBy(['email' => 'test@local.de']);
        $manager = $userRepo->findOneBy(['email' => 'test@local2.de']);


        self::assertNotNull($room);

        self::assertEquals($deputy, $room->getCreator());
        self::assertEquals($manager, $room->getModerator());

        self::assertEquals(1, sizeof($room->getUser()));
        self::assertEquals($manager, $room->getUser()[0]);


        self::assertEquals(1, $crawler->filter('.conference-name:contains("test for the supervisor")')->count());
        self::assertEquals(1, $crawler->filter('#room_card' . $room->getUidReal())->count());
        self::assertEquals(0, $crawler->filter('#room_card' . $room->getUidReal() . ' .btn:contains("Starten")')->count());
        self::assertEquals(2, $crawler->filter('#room_card' . $room->getUidReal() . ' .fa-solid.fa-users')->count());
        self::assertEquals(1, $crawler->filter('#room_card' . $room->getUidReal() . ' .moderator-options')->count());
        self::assertEquals(0, $crawler->filter('#room_card' . $room->getUidReal() . ' .moderator-sharelink')->count());
        self::assertEquals(0, $crawler->filter('#room_card' . $room->getUidReal() . ' .participants-remove')->count());
        self::assertEquals(0, $crawler->filter('#room_card' . $room->getUidReal() . ' .participants-participantList')->count());
        self::assertEquals(1, $crawler->filter('#room_card' . $room->getUidReal() . ' .moderator-options .moderator-edit')->count());
        self::assertEquals(0, $crawler->filter('#room_card' . $room->getUidReal() . ' .start-iframe')->count());
        self::assertEquals(0, $crawler->filter('#room_card' . $room->getUidReal() . ' .start-app')->count());

        $this->client->loginUser($this->manager);
        $crawler = $this->client->request('GET', '/room/dashboard');
        self::assertEquals(1, $crawler->filter('.createdByDeputy.loadContent')->count());
        self::assertEquals(1, $crawler->filter('.conference-name:contains("test for the supervisor")')->count());
        self::assertEquals(1, $crawler->filter('#room_card' . $room->getUidReal())->count());
        self::assertEquals(2, $crawler->filter('#room_card' . $room->getUidReal() . ' .btn:contains("Starten")')->count());
        self::assertEquals(2, $crawler->filter('#room_card' . $room->getUidReal() . ' .fa-solid.fa-users')->count());
        self::assertEquals(1, $crawler->filter('#room_card' . $room->getUidReal() . ' .moderator-options')->count());
        self::assertEquals(0, $crawler->filter('#room_card' . $room->getUidReal() . ' .moderator-sharelink')->count());
        self::assertEquals(0, $crawler->filter('#room_card' . $room->getUidReal() . ' .participants-remove')->count());
        self::assertEquals(0, $crawler->filter('#room_card' . $room->getUidReal() . ' .participants-participantList')->count());
        self::assertEquals(1, $crawler->filter('#room_card' . $room->getUidReal() . ' .moderator-options .moderator-edit')->count());
        self::assertEquals(2, $crawler->filter('#room_card' . $room->getUidReal() . ' .start-iframe')->count());
        self::assertEquals(1, $crawler->filter('#room_card' . $room->getUidReal() . ' .start-app')->count());
    }

    public function testDuplicateConference(): void
    {

        $userRepo = self::getContainer()->get(UserRepository::class);
        $deputy = $userRepo->findOneBy(['email' => 'test@local.de']);
        $manager = $userRepo->findOneBy(['email' => 'test@local2.de']);


        $server = $this->deputy->getServers()->toArray()[0];

        $crawler = $this->client->request('GET', '/room/new');
        $buttonCrawlerNode = $crawler->selectButton('Speichern');
        $form = $buttonCrawlerNode->form();
        $form['room[server]'] = $server->getId();
        $form['room[moderator]'] = $this->manager->getId();
        $form['room[name]'] = 'test for the supervisor';
        $form['room[start]'] = (new \DateTime())->format('Y-m-d H:i:s');
        $form['room[duration]'] = "60";

        $this->client->submit($form);
        $flash = $this->session->getBag('flashes')->all();


        $crawler = $this->client->request('GET', '/room/dashboard');
        self::assertResponseIsSuccessful();
        $roomRepo = self::getContainer()->get(RoomsRepository::class);
        $room = $roomRepo->findOneBy(['name' => 'test for the supervisor']);

        foreach ($this->deputy->getServers() as $s) {
            $this->deputy->removeServer($s);
        }
        $this->em->persist($this->deputy);
        foreach ($this->manager->getServers() as $s) {
            $this->manager->removeServer($s);
        }
        $this->em->persist($this->manager);
        $this->em->flush();

        $crawler = $this->client->request('GET', '/room/clone?room=' . $room->getId());

        $buttonCrawlerNode = $crawler->selectButton('Speichern');
        $form = $buttonCrawlerNode->form();
        $form['room[name]'] = 'test for the supervisor';
        $form['room[start]'] = (new \DateTime())->format('Y-m-d H:i:s');
        $form['room[duration]'] = "60";


        $this->client->submit($form);


        $crawler = $this->client->request('GET', '/room/dashboard');
        self::assertResponseIsSuccessful();
        $flashMessage = $crawler->filter('.snackbar')->text();
        self::assertEquals($flashMessage, 'Die Konferenz wurde erfolgreich erstellt.');
        $rooms = $roomRepo->findBy(['name' => 'test for the supervisor']);
        self::assertEquals(2, sizeof($rooms));
    }
}
