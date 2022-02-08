<?php
/**
 * Copyright © Wire IT All rights reserved.
 */

namespace WireIt\CustomerProfileImport\Console\Command;

use Exception;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterfaceFactory;
use Magento\Framework\Console\Cli;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\File\Csv;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\StoreManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Import Console
 */
class Import extends Command
{

    /**
     * Argument param for file path
     */
    const FILE_PATH = "file_path";

    /**
     * Argument param for profile type
     */
    const PROFILE_TYPE = "profile_type";

    /**
     *  Array Allowed profile type
     */
    const PROFILE_NAME = ["profile-json", "profile-csv"];

    /**
     * default password for new customer
     */
    const DEFAULT_PASSWORD = "admin@123";

    /**
     * @var Csv
     */
    protected $csv;

    /**
     * @var Json
     */
    protected $json;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManagerInterface;

    /**
     * @var CustomerInterfaceFactory
     */
    protected $customerFactory;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * @var EncryptorInterface
     */
    protected  $encryptor;

    /**
     * @param Csv $csvParser
     * @param Json $json
     * @param EncryptorInterface $encryptor
     * @param CustomerInterfaceFactory $customerFactory
     * @param CustomerRepositoryInterface $customerRepository
     * @param StoreManagerInterface $storeManagerInterface
     */
    public function __construct(
        Csv                         $csvParser,
        Json                        $json,
        EncryptorInterface          $encryptor,
        CustomerInterfaceFactory    $customerFactory,
        CustomerRepositoryInterface $customerRepository,
        StoreManagerInterface       $storeManagerInterface
    )
    {
        $this->csv = $csvParser;
        $this->json = $json;
        $this->encryptor = $encryptor;
        $this->customerFactory = $customerFactory;
        $this->customerRepository = $customerRepository;
        $this->storeManagerInterface = $storeManagerInterface;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName("customer:import");
        $this->setDescription("Import customers data via CLI, Support format (json,csv) ie: profile-csv path/to/sample.csv or profile-csv path/to/sample.json");
        $this->setDefinition([
            new InputArgument(self::PROFILE_TYPE, InputArgument::REQUIRED, "Name"),
            new InputArgument(self::FILE_PATH, InputArgument::REQUIRED, "Option functionality")
        ]);

        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(
        InputInterface  $input,
        OutputInterface $output
    )
    {
        $profileType = $input->getArgument(self::PROFILE_TYPE);
        $filePath = $input->getArgument(self::FILE_PATH);

        if (in_array($profileType, self::PROFILE_NAME) && file_exists($filePath)) {
            $fileExt = pathinfo($filePath, PATHINFO_EXTENSION);
            switch ($fileExt) {
                case 'csv':
                    $this->csv->setDelimiter(",");
                    try {
                        $dataArray = $this->csv->getData($filePath);
                    } catch (Exception $e) {
                        $output->writeln('<error>Error on reading csv file</error>');
                    }
                    break;
                case 'json':
                    $dataArray = $this->json->unserialize(file_get_contents($filePath));
                    break;
                default:
                    $output->writeln('<error>Given file type not allowed</error>');
            }
            if (!empty($dataArray)) {
                $this->importProfileData($dataArray, $fileExt, $output);
                $output->writeln("<info>Customer import is completed !</info>");
                return Cli::RETURN_SUCCESS;
            }
        } else {
            $output->writeln('<error>Please import with proper profile types(profile-json, profile-csv) with valid file path</error>');
        }
        return Cli::RETURN_FAILURE;
    }


    /**
     * @param array $data
     * @param string $type
     * @param OutputInterface $output
     * @return $this
     */
    public function importProfileData(array $data, string $type, OutputInterface $output)
    {
        $header = current($data);
        foreach ($data as $key => $values) {

            if ($type == "csv") {
                if ($key == 0) continue;
                $row = array_combine($header, $values);
            } else {
                $row = $values;
            }

            try {
                $customer = $this->customerFactory->create();
                $customer->setStoreId($this->storeManagerInterface->getStore()->getId());
                $customer->setWebsiteId($this->storeManagerInterface->getWebsite()->getId());
                $customer->setEmail($row['emailaddress']);
                $customer->setFirstname($row['fname']);
                $customer->setLastname($row['lname']);

                /** @var CustomerRepositoryInterface $customerRepository */
                $this->customerRepository->save($customer, $this->encryptor->getHash(self::DEFAULT_PASSWORD,true));

                $output->writeln('<info>Customer imported ' . $row['emailaddress'] . '</info>');
            } catch (Exception $exception) {
                $output->writeln('<error>Error on import ' . $row['emailaddress'] . ' ,Error : ' . $exception->getMessage() . '</error>');
                continue;
            }
        }
        return $this;
    }

}
