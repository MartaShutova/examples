<?php

namespace Bundles\CoreBundle\Command;

use Bundles\CoreBundle\Model\Payments\TransactionQuery;
use Bundles\CoreBundle\Model\Payments\TransactionType;
use Bundles\CoreBundle\Model\Products\ProductCategoryDepositStackQuery;
use Bundles\CoreBundle\Model\Registrations\RegistrationQuery;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AddMDACountryDateCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('core:add_mda_country_date')
            ->addArgument('connection', InputArgument::REQUIRED, 'MySQL DB name to use');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->getContainer()->get('database_switcher')->changeDatabase($input->getArgument('connection'));

        $registrations = RegistrationQuery::create()
            ->useProductQuery()
                ->useProductCategoryQuery()
                    ->filterByDeposit(0, \Criteria::GREATER_THAN)
                ->endUse()
            ->endUse()
            ->filterByCountryMdaDate(null, \Criteria::ISNULL)
            ->_or()
            ->filterByCountryMdaDate('0000-00-00',\Criteria::EQUAL)
            ->find();

        foreach ($registrations as $registration) {
            $user = $registration->getUser();
            $nationality = $user->getLastAddressLine()->getNationalityId();
            if ($nationality) {
                $depositStack = ProductCategoryDepositStackQuery::create()
                    ->filterByNationalityId($nationality)
                    ->filterByDateFrom($registration->getApplication()->getCreateDate('Y-m-d'), \Criteria::LESS_EQUAL)
                    ->filterByDateTo($registration->getApplication()->getCreateDate('Y-m-d'), \Criteria::GREATER_EQUAL)
                    ->useProductCategoryQuery()
                        ->filterById($registration->getProduct()->getProductCategoryId())
                    ->endUse()
                    ->findOne();
                if ($depositStack) {
                    $output->writeln('depositStack - '.$depositStack->getId());
                    $depositCountry = $depositStack->getCountryDeposit();
                    if ($depositCountry > 0 && !$registration->getCountryMdaDate()) {
                        $amount = 0;
                        $transactions = $registration->getPaidTransactionsWithoutNR();
                        $output->writeln('Count - '.count($transactions));
                        foreach ($transactions as $transaction) {
                            $output->writeln('Transaction - '.$transaction->getId());
                            $amount += $transaction->getAmount();
                            $output->writeln('Transaction amount - '.$amount);
                            if ($amount >= $depositCountry) {
                                $registration
                                    ->setCountryMdaDate($transaction->getPaid())
                                    ->save();
                                break;
                            }
                        }
                    }
                }
            }
        }
    }
}
