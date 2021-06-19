# Network Monitor

Network monitoring application for ubuntu written in PHP.

## Requirements

- PHP ^8.0
- Linux based operating system (Tested on Ubuntu 20.04)
- vnstat

## Installation

````shell
git clone https://github.com/isaeken/php-network-monitor.git network-monitor &&
cd network-monitor
````

````shell
composer install
````

````shell
sudo apt-get install vnstat &&
sudo service vnstat enable &&
sudo service vnstat start
````

## Usage

### Using with CLI

````shell
composer run monitor <interface> <?refresh>
composer run monitor eth0
composer run monitor eth0 refresh
````

### Using in web

Host public folder using web server. (Nginx recommended)

Host using PHP development server:

````shell
php -S 0.0.0.0:80 public
````

## LICENSE

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
