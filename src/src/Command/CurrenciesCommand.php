<?php

namespace App\Command;

use App\Entity\Currency;
use App\Entity\Rate;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CurrenciesCommand extends Command
{
    protected static $defaultName = 'rss:fill';
    private EntityManagerInterface $doctrine;
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $doctrine, EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->doctrine = $doctrine;
        $this->entityManager = $entityManager;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $feed = \Feed::loadRss('https://www.bank.lv/vk/ecb_rss.xml')->toArray();
        $items = $feed['item'];
        $progressBar = new ProgressBar($output, count($items));
        $progressBar->start();
        foreach ($items as $item) {
            $results = [];
            $test = explode(' ', $item['description']);
            $test = array_filter($test, fn($value) => !is_null($value) && $value !== '');
            $date = new \DateTime($item['pubDate']);

            for ($i = 0; $i < count($test); $i += 2) {
                $results[$test[$i]] = $test[$i + 1];
            }
            foreach ($results as $currency => $rate) {
                $currencyEntity = $this->doctrine->getRepository(Currency::class)
                    ->findOneBy(['name' => $currency]);
                if (!$currencyEntity) {
                    $currencyEntity = new Currency();
                    $currencyEntity->setName($currency);
                    $this->entityManager->persist($currencyEntity);
                    $this->entityManager->flush();
                }
                $rateEntity = $this->doctrine
                    ->getRepository(Rate::class)
                    ->findOneBy(['currency' => $currencyEntity, 'date' => $date]);
                if (!$rateEntity) {
                    $rateEntity = new Rate();
                    $rateEntity->setCurrency($currencyEntity);
                    $rateEntity->setDate($date);
                    $rateEntity->setValue($rate);
                    $this->entityManager->persist($rateEntity);
                    $this->entityManager->flush();
                }
            }
            $progressBar->advance();
        }
        $progressBar->finish();
        return Command::SUCCESS;
    }
}