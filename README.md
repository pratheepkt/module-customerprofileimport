# module-customerprofileimport

Magento version support 2.X

Install extension (Sample customer account creations)

#Go to Magento root path

Download composer package
composer require poongud/module-customerprofileimport

# Extension installation
* php bin/magento module:enable WireIt_CustomerProfileImport
* php bin/magento setup:upgrade
* php bin/magento setup:di:compile (skip this step in developer mode)
* php bin/magento setup:static-content:deploy

Import customer data:
  * This import process will support for csv and json format. Follow the bellow command to import the customer data with password.
  * Import file should upload into magento server and take note of file path.
  
# Command to import
# CSV 
 php bin/magento customer:import profile-csv ./to/filepath/sample.csv
 
 ![image](https://user-images.githubusercontent.com/16649626/153092976-3a4d8b7a-171b-4293-8c70-a1a60204e07e.png)

# Json
 php bin/magento customer:import profile-json ./to/filepath/sample.json
 
 ![image](https://user-images.githubusercontent.com/16649626/153093074-18692268-16d2-4721-8300-250ee0459532.png)

