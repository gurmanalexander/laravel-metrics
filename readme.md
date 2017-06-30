# Laravel Metrics
This package helps you to manage your application metrics (e.g. Time, Count, Money)

## Table of Contents
- <a href="#installation">Installation</a>
- <a href="#usage">Usage</a>
    - <a href="#create-metrics">Create Metrics</a>
    - <a href="#metrics-usage">Metrics usage</a>
    - <a href="#start-metrics">Start Metrics</a>
    - <a href="#stop-metrics">Stop Metrics</a>
    - <a href="#once-metrics">Once Metrics</a>
- <a href="#statistics">Statistics</a>
    - <a href="#methods">Methods</a>
        - <a href="#user">user()</a>
        - <a href="#admin">admin()</a>
        - <a href="#startat">startAt()</a>
        - <a href="#endat">endAt()</a>
        - <a href="#betweenat">betweenAt()</a>
        - <a href="#period">period</a>
        - <a href="#getbuilder">getBuilder()</a>
    - <a href="#results">Results</a>
        - <a href="#count">count()</a>
        - <a href="#sum">sum()</a>
        - <a href="#avg">avg()</a>
        - <a href="#min">min()</a>
        - <a href="#max">max()</a>


## Installation

Require this package with composer:

```shell
composer require gurmanalexander/laravel-metrics
```

After updating composer, add the ServiceProvider to the providers array in config/app.php

> Laravel 5.5 uses Package Auto-Discovery, so doesn't require you to manually add the ServiceProvider


### Laravel 5.x:

```php
GurmanAlexander\Metrics\MetricsServiceProvider::class,
```

Copy the package config to your local config with the publish command:

```shell
php artisan vendor:publish --provider="GurmanAlexander\Metrics\MetricsServiceProvider"
```

You may use the `metrics:table` command to generate a migration with the proper table schema:

```shell
php artisan metrics:table
```

And then migrate:

```shell
php artisan migrate
```

## Usage
### Create Metrics
You can crete new Metrics (default CountMetrics), but you can change it to TimeMetrics with parameter `--time`
```shell
php artisan make:metrics PageViewCountMetrics
```

Creating TimeMetrics example:
```shell
php artisan make:metrics FirstPaymentMetrics
```

This will create new class in `app/Metrics/` folder
```php
class FirstPaymentMetrics extends CountMetrics
{

}
```

### Metrics usage
Now you can start watching your Metrics. You need to add trait `Metricable` to your Model (e.g. User), that you want watching
```php
use GurmanAlexander\Metrics\Metricable;
class User extends Model
{
    use Metricable;
    ...
}
```

### Start metrics
To start Metrics:
> In CountMetrics first parameter - `$user` (The user to which the metrics belongs, default `null`),
> second parameter - `admin` (The user who called the metrics, default `null`),
> third parameter - `$count` (How much to increase the metrics. For example, you can use money metrics. default `1`)

> In TimeMetrics only two parameters - `$user` and `$admin`
```php
// For example, when creating user start Metrics
$user = User::create(['email', 'password']);
$user->startMetrics(new FirstPaymentMetrics($user));
```
or
```php
// when user view some news
$user = auth()->user();
$news->startMetrics(new PageViewCountMetrics($user));
```

### Stop metrics
To stop Metrics:
```php
// when user make some payment
$user->paySomething();
$user->closeMetrics(new FirstPaymentMetrics($user));
```
or
```php
// when user logout
$user = auth()->user();
$news->closeMetrics(new PageViewCountMetrics($user));
auth()->logout();
```

### Once metrics
To fire once Metrics:
```php
$user = auth()->user();
$user->onceMetrics(new SomeOnceMetrics());
```

## Statistics
To get statistics you can use `MetricsStatistics` class

Example:
```php
// to get total payments
$stats = new MetricsStatistics(new FirstPaymentMetrics());
```

### Methods

#### user
Filter statistics by `$user` (user Model, array of users or Collection of users)
> `$user` - The user to which the metrics belongs.
```php
// model
$stats->user(auth()->user());

// array
$stats->user([auth()->user(), User:first()]);

// collection
$stats->user(User::where('is_active', 1)->get());
```

#### admin
Filter by **admin** like by **user**
> `admin` - The user who called the metrics.

#### startAt
Filter from `$start_at` date
> The metrics stats calculating by `end_at` field (when metrics stops)
```php
$start_at = Carbon::now()->startOfMonth();
$stats->startAt($start_at);
```

#### endAt
Filter to `$end_at` date
> The metrics stats calculating by `end_at` field (when metrics stops)
```php
$end_at = Carbon::now()->endOfMonth();
$stats->endAt($end_at);
```

#### betweenAt
Filter from `$start_at` to `$end_at` date
> The metrics stats calculating by `end_at` field (when metrics stops)
```php
$start_at = Carbon::now()->startOfMonth();
$end_at = Carbon::now()->endOfMonth();
$stats->betweenAt($start_at, $end_at);
```

#### period
Calculating statistics grouped by periods
```php
$stats->hourly();
$stats->daily();
$stats->weekly();
$stats->monthly();
$stats->yearly();

// result example
$stats->hourly()->count()->toArray();

// [
//     0 => [
//         "count" => 23,
//         "date" => "2017-06-30",
//         "period" => "hour",
//         "hour" => 9
//     ],
//     1 => [
//         "count" => 15,
//         "date" => "2017-06-30",
//         "period" => "hour",
//         "hour" => 8
//     ],
//     2 => [
//         "count" => 32,
//         "date" => "2017-06-30",
//         "period" => "hour",
//         "hour" => 7
//     ],
// ]
```

#### getBuilder
return Builder to your custom calculating

### Results
to get results

#### count
return **Count** of all filtered Metrics in DB
> return Collection of Metrics

#### sum
return **Sum** of all filtered Metrics in DB
> if this is TimeMetrics - total seconds for metrics

#### avg
return **Average** of all filtered Metrics in DB
> if this is TimeMetrics - average seconds for metrics

#### min
return **Min** of all filtered Metrics in DB
> if this is TimeMetrics - min seconds for metrics

#### max
return **Max** of all filtered Metrics in DB
> if this is TimeMetrics - max seconds for metrics



### Example:
```php
// to get total payments
$stats = new MetricsStatistics(new FirstPaymentMetrics());

// to get average time from user registration to first payment (in seconds)
$stats = new MetricsStatistics(new FirstPaymentMetrics())->hourly()->avg()->toArray();

// [
//     0 => [
//         "avg" => 12.13,
//         "date" => "2017-06-30",
//         "period" => "hour",
//         "hour" => 9
//     ],
//     1 => [
//         "avg" => 8.00,
//         "date" => "2017-06-30",
//         "period" => "hour",
//         "hour" => 8
//     ],
//     2 => [
//         "avg" => 5.34,
//         "date" => "2017-06-30",
//         "period" => "hour",
//         "hour" => 7
//     ],
// ]
```

