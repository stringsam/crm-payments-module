<?php

namespace Crm\PaymentsModule\Commands;

use Crm\PaymentsModule\MailConfirmation\MailProcessor;
use Crm\PaymentsModule\MailConfirmation\SkCsobMailDownloader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SkCsobMailConfirmationCommand extends Command
{
    private $mailDownloader;

    private $mailProcessor;

    public function __construct(
        SkCsobMailDownloader $mailDownloader,
        MailProcessor $mailProcessor
    ) {
        parent::__construct();
        $this->mailDownloader = $mailDownloader;
        $this->mailProcessor = $mailProcessor;
    }

    protected function configure()
    {
        $this->setName('payments:sk_csob_mail_confirmation')
            ->setDescription('Check notification emails and confirm payments based on slovak CSOB emails');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->mailDownloader->download(function ($mailContent) use ($output) {
            return $this->markMailProcessed($mailContent, $output);
        });

        return 0;
    }

    private function markMailProcessed(array $mailContents, OutputInterface $output)
    {
        $result = true;
        foreach ($mailContents as $mailContent) {
            $processed = $this->mailProcessor->processMail($mailContent, $output);
            if (!$processed) {
                $result = false;
            }
        };

        return $result;
    }
}
