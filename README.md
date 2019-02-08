<h3>Savvytech.com Integration for Magento 2</h3>
<h3>What is this repo?</h3>
This repo contains a payment plugin for WooCommerce shopping cart to support crypto currencies via Savvy. Supported currencies are Bitcoin, Bitcoin Cash, Bitcoin Gold, Ethereum, Ethereum Classic, Litecoin, Dash, Dai, and Binance Coin.

Users have the opportunity to manage the currencies they would like to accept in their settings at https://www.savvy.io

<h3>Who do we expect to use this documentation?</h3>
You’re in the right place if you’re a developer or a shop owner looking to integrate a new payment method into your shopping cart.

Attention to PayBear users: if you have PayBear plugin installed, please [read this](https://github.com/savvytechcom/savvy-samples/wiki/Upgrading-from-V2-to-V3)

<h3>Prerequisites</h3>
Before installing the plugin please make sure you have the latest version of Magento 2 installed. We support version 2.x

In order to use the plugin you will also need a Savvy API Key. Getting a key is free and easy:

 1. Sign up to https://www.savvy.io and create a personal wallet.
 2. Click the Merchant button on the left to enable merchant features.
 3. Create a merchant wallet using the existing sending password.
 4. Click Profile -> Settings -> Merchant tab
 5. Confirm the currencies you would like to accept.
 6. Your API Keys can be found below on the same page.

<h3>Install ready-to-paste package</h3>
 
 1. Download the latest version of the integration: https://github.com/savvytechcom/savvy-magento2/releases/latest
 2. Extract the package and connect to your server using SFTP Clients. Then upload the app folder to Magento 2 root folder.
 3. To complete the installation process you need to run following commands: <br>
    php bin/magento setup:upgrade <br>
    php bin/magento cache:clean
 4. Log in to your Magento Administration page as administrator 
 5. Go to Stores → Configuration → Sales → Payment Methods → Savvy Payments
 6. Select Enabled -> Yes and add you Api Key (Secret)
 
 <h3>How to update</h3>
 1. Download the latest version of the integration: https://github.com/savvytechcom/savvy-magento2/releases/latest <br>
 2. Extract the package and connect to your server using SFTP Clients. Then upload the app folder to Magento 2 root folder.<br>
 3. To complete the update process you need to run following commands: <br>
     php bin/magento setup:upgrade <br>
     php bin/magento cache:clean

<h3>Get Help</h3>
Start with our <a href="https://help.savvy.io">Knowledge Base</a> and <a href="https://help.savvy.io/frequently-asked-questions">FAQ</a>.

Still have questions or need support? Log in to your Savvy account and use the live chat to talk to our team directly!
