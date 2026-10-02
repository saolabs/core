<?php

namespace Tests\Unit;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\View;
use Saola\Core\Mailer\Email;
use Saola\Core\Mailer\Job;
use Tests\TestCase;

class MailerEmailConfigTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        $app['config']->set('mail.default', 'array');
        $app['config']->set('mail.from', ['address' => 'hello@example.test', 'name' => 'Crazify']);
        $app['config']->set('mail.mailers.smtp.scheme', 'smtps');
    }

    protected function setUp(): void
    {
        parent::setUp();
        View::addNamespace('coremail', __DIR__.'/../fixtures/mail');
    }

    private function hello(): Email
    {
        return Email::to('lan@example.test', 'Lan')->subject('Xin chào')->body('coremail::hello')->data(['name' => 'Lan']);
    }

    public function test_the_application_mail_config_is_left_as_is(): void
    {
        $before = config('mail');

        new Email;

        $this->assertSame($before, config('mail'));
        $this->assertSame('smtps', config('mail.mailers.smtp.scheme'));
    }

    public function test_sent_mail_uses_the_configured_sender_name(): void
    {
        $this->hello()->send();

        $sent = Mail::getSymfonyTransport()->messages()->sole()->getOriginalMessage();
        $this->assertSame('"Crazify" <hello@example.test>', $sent->getFrom()[0]->toString());
        $this->assertSame('Xin chào', $sent->getSubject());
        $this->assertStringContainsString('Chào Lan', $sent->getHtmlBody());
    }

    public function test_send_after_sends_now_unless_the_mail_queue_is_enabled(): void
    {
        Queue::fake();

        $this->hello()->sendAfter(1);
        $this->assertCount(1, Mail::getSymfonyTransport()->messages(), 'queue not enabled: sent right away');
        Queue::assertNothingPushed();

        config(['mail.queue.enabled' => true]);
        $this->hello()->sendAfter(1);
        Queue::assertPushed(Job::class);
        $this->assertCount(1, Mail::getSymfonyTransport()->messages());
    }
}
