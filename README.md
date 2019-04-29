# PHP-C45
C45 algorithm implementation in pure PHP

**NOTE : this implementation doesn't support numeric value for data training. I don't have any plan to implement it. Feel free to send a pull request.**

## Installation
The preferred way to install this extension is through composer.

Either run

```
php composer.phar require --prefer-dist juliardi/c45 "*"
```
or add

```
"juliardi/c45": "*"
```

to the require section of your composer.json file.

## Requirement
This library doesn't need any special configuration. You can use it as is. However, there are a few requirement that must be met in order to make this library run as intended.
1. **You can only use CSV file as input for training data.** It is a bad decision and I admit it. However as of now i don't have time to implements a more generic way to read training data.
2. **The first line of the CSV file MUST be the attribute name and MUST be quoted in double quotes**

Here is an example of a valid CSV for training :
```
"outlook", "windy", "humidity", "play"
sunny, false, high, no
sunny, true, high, no
sunny, false, high, no
sunny, false, medium, yes
sunny, true, medium, yes
overcast, false, medium, yes
overcast, true, medium, yes
overcast, true, high, yes
overcast, false, medium, yes
rain, false, high, yes
rain, false, medium, yes
rain, true, medium, no
rain, false, medium, yes
rain, true, medium, no
```

## Usage
Assuming that you have the following directory structure :
```
.
├── src
|   ├── index.php
|   └── data.csv
├── vendor
├── composer.json
```

You can use PHP-C45 in your `index.php` like this :
```php
<?php

require_once __DIR__ . '/../vendor/autoload.php';

use C45\C45;

$filename = __DIR__ . '/data.csv';

$c45 = new C45([
                'targetAttribute' => 'play',
                'trainingFile' => $filename,
                'splitCriterion' => C45::SPLIT_GAIN,
            ]);

$tree = $c45->buildTree();
$treeString = $tree->toString();

// print generated tree
echo '<pre>';
print_r($treeString);
echo '</pre>';

$testingData = [
  'outlook' => 'sunny',
  'windy' => 'false',
  'humidity' => 'high',
];

echo $tree->classify($testingData); // prints 'no'
```
