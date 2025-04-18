
# guesthouse and hotel management software FewohBee

*For german version see: [README.de.md](https://github.com/developeregrem/fewohbee/blob/master/README.de.md)*

The hotel management software for small to medium-sized pensions and hotels - open source and free of charge.

The guesthouse management tool or property managment system (PMS) is a PHP project based on the amazing PHP framework Symfony.
Small guesthouses or accommodations usually manage their rooms or apartments the old way by using a pen and a sheet of paper or using a spreadsheet. The goal of this open-source tool is to help smaller accommodations to replace the hand written approach to manage rooms, to improve productivity by combining all information which, finally, ends in a time reduction to manage the guesthouse.

*For a detailed documentation please refer to the [Wiki](https://github.com/developeregrem/fewohbee/wiki).*


## Features

 - easy way to add and manage reservations (reservation overview)
 - manage your guest data (including a GDPR export function)
 - extensive settings to manage your
	 - rooms, accommodations, prices, reservation origins, templates, etc.
 - create invoices (PDF)
 - conversations (write mails from within the tool), send invoices, reservation confirmations or other relevant information to the guest
 - statistics
 - registration book
 - cash book to manage your income and outcome
 - calendar export for other applications (iCal sync)

## Requirements

In order to use the tool, you need to have a small web server fulfilling the Symfony [requirements](https://symfony.com/doc/current/setup.html#technical-requirements). This means:

 - PHP 8.2 or higher
 - php-intl extension installed and activated
 - a web server e.g. nginx or apache
 - a database server (recommended is mysql or mariadb)

## Quick Start

> It's recommended to use the docker-compose setup: [fewohbee-dockerized](https://github.com/developeregrem/fewohbee-dockerized)

Create a database for the tool:

    CREATE DATABASE fewohbee CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

 Copy the file `.env.dist` and name the new file `.env`.

Edit the file `.env` and update the property `DATABASE_URL` to meet your database settings.
Generate a random value and update the property `APP_SECRET` (you can use the following site to generate a random value: [Link](http://nux.net/secret))

If not already available download the PHP dependency manager [composer](https://getcomposer.org/download/) in order to install project dependencies. Afterwards run the following command within the root folder of the project:

    composer install

Run the following command to initialize the database and the application:

    php bin/console doctrine:migration:migrate
    php bin/console app:first-run

    // optional: add some test data
    php bin/console doctrine:fixtures:load --append

Now, you are ready to open a browser, navigate to the installation folder e.g. 
http://localhost/fewohbee/public/index.php
and login with the user created in the step before.

## Using Docker

To run the application using docker a pre-configured docker-compose setup is available. Please refer to the documentation in the Wiki: [Docker-Setup](https://github.com/developeregrem/fewohbee/wiki/Docker-Setup)

## i18n

The tool has multi language support by design and is available in german and english. If you have a language request to support other languages, please open a ticket. 

## Author

Alexander Elchlepp

This project is a one man show at the moment and developed in my free time since 2014. If you have questions open a ticket or contact me at info (at) fewohbee.de

If you want to support this project and you think the project is useful for you I would be very happy about a small donation :)
[![Donate](https://img.shields.io/badge/Donate-PayPal-green.svg)](https://www.paypal.com/donate?hosted_button_id=ZQPG864PB4TBE)
