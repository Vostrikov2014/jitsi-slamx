<?php

namespace App\Tests;

use App\Service\Lobby\DirectSendService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Mercure\Jwt\StaticTokenProvider;
use Symfony\Component\Mercure\MockHub;
use Symfony\Component\Mercure\Update;
use Twig\Environment;

class LobbyDirectSendTest extends KernelTestCase
{
    public function testSnackbar(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $directSend = $this->getContainer()->get(DirectSendService::class);


        $hub = new MockHub('http://localhost:3000/.well-known/mercure', new StaticTokenProvider('test'), function (Update $update): string {
            self::assertEquals('{"type":"snackbar","message":"TestText","color":"danger"}', $update->getData());
            self::assertEquals(['test/test/numberofUser'], $update->getTopics());
            return 'id';
        });
        $directSend->setMercurePublisher($hub);
        $directSend->sendSnackbar('test/test/numberofUser', 'TestText', 'danger');
    }

    public function testBrowserNotification(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $directSend = $this->getContainer()->get(DirectSendService::class);


        $hub = new MockHub('http://localhost:3000/.well-known/mercure', new StaticTokenProvider('test'), function (Update $update): string {
            self::assertEquals('{"type":"notification","title":"Title of Browser Notification","message":"I`m the message which is in the body part","pushNotification":"I`m the message in the pushnotification from the OS","messageId":"'.md5('Title of Browser Notification'.'I`m the message which is in the body part').'"}', $update->getData());
            self::assertEquals(['test/test/numberofUser'], $update->getTopics());
            return 'id';
        });
        $directSend->setMercurePublisher($hub);
        $id = md5('Title of Browser Notification'.'I`m the message which is in the body part');
        $directSend->sendBrowserNotification('test/test/numberofUser', 'Title of Browser Notification', 'I`m the message which is in the body part','I`m the message in the pushnotification from the OS',$id);
    }

    public function testRedirectResponse(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $directSend = $this->getContainer()->get(DirectSendService::class);


        $hub = new MockHub('http://localhost:3000/.well-known/mercure', new StaticTokenProvider('test'), function (Update $update): string {
            self::assertEquals('{"type":"redirect","url":"\/rooms\/testMe","timeout":1000}', $update->getData());
            self::assertEquals(['test/test/numberofUser'], $update->getTopics());
            return 'id';
        });
        $directSend->setMercurePublisher($hub);
        $directSend->sendRedirect('test/test/numberofUser', '/rooms/testMe', 1000);
    }

    public function testRefreshResponse(): void
    {
        $kernel = self::bootKernel();

        $this->assertSame('test', $kernel->getEnvironment());
        $directSend = $this->getContainer()->get(DirectSendService::class);


        $hub = new MockHub('http://localhost:3000/.well-known/mercure', new StaticTokenProvider('test'), function (Update $update): string {
            self::assertEquals('{"type":"refresh","reloadUrl":"\/rooms\/testMe #testId"}', $update->getData());
            self::assertEquals(['test/test/numberofUser'], $update->getTopics());
            return 'id';
        });
        $directSend->setMercurePublisher($hub);
        $directSend->sendRefresh('test/test/numberofUser', '/rooms/testMe #testId');
    }

    public function testModal(): void
    {
        $kernel = self::bootKernel();
        $this->assertSame('test', $kernel->getEnvironment());
        $directSend = $this->getContainer()->get(DirectSendService::class);
        $twig = self::getContainer()->get(Environment::class);
        $content = $twig->render('lobby_participants/choose.html.twig', array('appUrl' => 'https://test.de/app', 'browserUrl' => 'https://test.de/browser'));

        $hub = new MockHub('http://localhost:3000/.well-known/mercure', new StaticTokenProvider('test'), function (Update $update): string {
            self::assertEquals('{"type":"modal","content":"<div class=\"modal-dialog modal-dialog-centered\">\n    <div class=\"modal-content\">\n        <div class=\"modal-header  light-blue darken-3 white-text\">\n            <h5 class=\"modal-title\">Der Moderator hat Sie zur Konferenz hinzugef\u00fcgt<\/h5>\n            <button style=\"color: white\" type=\"button\" class=\"close\" data-dismiss=\"modal\" aria-label=\"Close\">\n                <span aria-hidden=\"true\">\u00d7<\/span>\n            <\/button>\n        <\/div>\n        <div class=\"modal-body\">\n           <p>Sie haben die Wahl ob Sie mit dem Browser oder der Jitsi-Meet Electro App dem Meeting beitreten wollen. Sind Sie sich unsicher, w\u00e4hlen Sie &quot;Im Browser&quot;.<\/p>\n        <\/div>\n        <div class=\"btn-group\">\n            <a href=\"https:\/\/test.de\/browser\" class=\"btn btn-outline-primary\">Im Browser<\/a>\n            <a href=\"https:\/\/test.de\/app\" class=\"btn btn-outline-primary\">In der App<\/a>\n        <\/div>\n\n    <\/div>\n<\/div>"}', $update->getData());
            self::assertEquals(['test/test/numberofUser'], $update->getTopics());
            return 'id';
        });
        $directSend->setMercurePublisher($hub);
        $directSend->sendModal('test/test/numberofUser', $content);
    }
}
