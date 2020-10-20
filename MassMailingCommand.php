<?php

namespace RichBrains\Bundles\CoreBundle\Command;

use PHPExcel_IOFactory;
use PHPExcel_Worksheet;
use Propel;
use RichBrains\Bundles\CoreBundle\Model\Applications\ApplicationQuery;
use RichBrains\Bundles\CoreBundle\Model\Others\Company;
use RichBrains\Bundles\CoreBundle\Model\Others\CountryQuery;
use RichBrains\Bundles\CoreBundle\Model\Students\UserQuery;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MassMailingCommand extends ContainerAwareCommand
{

    public function configure()
    {
        $this
            ->setName('mass_mailing')
            ->setDescription('Generates propel migration for new campus')
            ->addArgument('type', InputArgument::REQUIRED, 'This argument will use as type name.')
            ->addArgument('db', InputArgument::REQUIRED, 'This argument will use as database name.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $type = $input->getArgument('type');
        $db = $input->getArgument('db');

        $this->getContainer()->get('database_switcher')->changeDatabase($db);

        Propel::setForceMasterConnection(true);
        $con = Propel::getConnection();
        try {
            $file = $this->getContainer()->get('kernel')->getRootDir().'/../temp/mass_mailing/'.$type.'.xlsx';
            $inputFileType = PHPExcel_IOFactory::identify($file);
            $objReader = PHPExcel_IOFactory::createReader($inputFileType);
            $objPHPExcel = $objReader->load($file);

            $sheet = $objPHPExcel->getSheet(0);
            $this->processSheet($sheet, $type, $output);

        } catch (\Exception $e) {
            $con->rollBack();
            $this->getContainer()->get('db.logger')->error($e->__toString(), ['context' => 'CorpRegImport']);
        }
    }

    private function processSheet(PHPExcel_Worksheet $sheet, $tmplName, $output) {

        try {

            $highestRow = $sheet->getHighestRow();

            for ($row = 2; $row <= $highestRow; $row++) {

                $userData = [];
                $userData['sno'] = trim($sheet->getCellByColumnAndRow(0, $row)->getFormattedValue());
                $userData['title'] = $sheet->getCellByColumnAndRow(1, $row)->getFormattedValue();
                $userData['birthDay'] = $sheet->getCellByColumnAndRow(2, $row)->getFormattedValue();
                $userData['email'] = $sheet->getCellByColumnAndRow(3, $row)->getFormattedValue();
                $userData['appName'] = $sheet->getCellByColumnAndRow(4, $row)->getFormattedValue();
                $userData['appStartDay'] = $sheet->getCellByColumnAndRow(5, $row)->getFormattedValue();
                $userData['appEndDay'] = $sheet->getCellByColumnAndRow(6, $row)->getFormattedValue();

                $user = UserQuery::create()->findOneBySno($userData['sno']);

                if ($user !== null) {

                    $application = ApplicationQuery::create()
                        ->filterByUserId($user->getId())
                        ->filterByTitle($userData['appName'])
                        ->findOne();

                    if ($application !== null) {

                        $userData['countryOfBirth'] = '';
                        if ($addressData = $user->getLastAddressLine()) {
                            if ($countryOfBirthId = $addressData->getBirthCountryId()) {
                                if ($country = CountryQuery::create()->findOneById($countryOfBirthId))
                                    $userData['countryOfBirth'] = $country->getName();
                            }
                        }

                        $this->getContainer()->get('sqs')->sendMessage('user-mail-send', serialize([
                            'userId' => $user->getId(),
                            'applicationId' => $application->getId(),
                            'userData' => $userData,
                            'region' => $tmplName,
                            'db' => Company::getCurrent()->getDbName(),
                        ]));

                    } else {
                        $output->writeln('Row ' . $row . '; Application "' . $userData['appName'] . '" not found.' . ' User ' . $userData['sno']);
                    }

                } else {
                    $output->writeln('Row ' . $row . '; User ' . $userData['sno'] . ' not found');
                }

            }


        } catch (\Exception $e) {
            $output->writeln("\r{$e->getMessage()}");
        }
    }
}
