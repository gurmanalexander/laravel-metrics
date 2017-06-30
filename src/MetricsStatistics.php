<?php namespace GurmanAlexander\Metrics;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MetricsStatistics
{

    const HOURLY    = 'hour';
    const DAILY     = 'day';
    const WEEKLY    = 'week';
    const MONTHLY   = 'month';
    const YEARLY    = 'year';

    protected $metrics;
    protected $metricsClass;
    protected $metricsModel;
    protected $startAt;
    protected $endAt;
    protected $period = self::WEEKLY;

    function __construct(Metrics $metrics)
    {
        $this->metricsClass = get_class($metrics);
        $this->metricsModel = $metrics;

        $this->metrics  = call_user_func(get_class($metrics) . '::where', ['type' => get_class($metrics)]);
        $this->metrics
            ->where('type', get_class($metrics))
            ->whereNotNull('end_at')
            ->orderBy('end_at', 'desc')
        ;

        $this->startAt  = Carbon::now()->startOfMonth();
        $this->endAt    = Carbon::now();
    }

    /**
     * Filter by date "start from" calculating statistics
     *
     * @param Carbon|null $start_at
     * @return MetricsStatistics
     */
    public function startAt(Carbon $start_at = null)
    {
        if ($start_at) $this->startAt = $start_at;

        $this->metrics->where('end_at', '>=', $this->startAt);

        return $this;
    }

    /**
     * Filter by date "end to" calculating statistics
     *
     * @param Carbon|null $end_at
     * @return MetricsStatistics
     */
    public function endAt(Carbon $end_at = null)
    {
        if ($end_at) $this->endAt = $end_at;

        $this->metrics->where('end_at', '>=', $this->endAt);

        return $this;
    }

    /**
     * Filter by date "start from" to "end to" calculating statistics
     *
     * @param Carbon|null $start_at
     * @param Carbon|null $end_at
     * @return MetricsStatistics
     */
    public function betweenAt(Carbon $start_at = null, Carbon $end_at = null)
    {
        if ($start_at) $this->startAt = $start_at;
        if ($end_at) $this->endAt = $end_at;
        $this->metrics->whereBetween('end_at', [$this->startAt, $this->endAt]);

        return $this;
    }

    /**
     * Filter by user
     *
     * @param \App\User|array|null $user
     * @return MetricsStatistics
     */
    public function user($user = null)
    {
        $this->filterUser($user, 'user_id');

        return $this;
    }

    /**
     * Filter by admin
     *
     * @param \App\User|array|null $user
     * @return MetricsStatistics
     */
    public function admin($user = null)
    {
        $this->filterUser($user, 'admin_id');

        return $this;
    }

    protected function period($period)
    {
        if (in_array($period, [self::HOURLY, self::DAILY, self::WEEKLY, self::MONTHLY, self::YEARLY])) {
            $this->period = $period;
        }


        switch ($this->period) {
            case self::WEEKLY: {
                $groupBy = 'WEEK(end_at, 1)';
            } break;
            default: {
                $groupBy = strtoupper($this->period) . '(end_at)';
            }
        }

        $this->metrics->groupBy(DB::raw($groupBy));

        return $this;
    }

    /**
     * Calculating result hourly
     *
     * @return MetricsStatistics
     */
    public function hourly()
    {
        return $this->period(self::HOURLY);
    }

    /**
     * Calculating result daily
     *
     * @return MetricsStatistics
     */
    public function daily()
    {
        return $this->period(self::DAILY);
    }

    /**
     * Calculating result weekly
     *
     * @return MetricsStatistics
     */
    public function weekly()
    {
        return $this->period(self::WEEKLY);
    }

    /**
     * Calculating result monthly
     *
     * @return MetricsStatistics
     */
    public function monthly()
    {
        return $this->period(self::MONTHLY);
    }

    /**
     * Calculating result yearly
     *
     * @return MetricsStatistics
     */
    public function yearly()
    {
        return $this->period(self::YEARLY);
    }

    /**
     * @param \App\User|array|null $user
     * @param string $foreign_key
     * @return MetricsStatistics
     */
    protected function filterUser($user = null, $foreign_key = 'user_id')
    {
        if (!$user) return $this;

        if (is_array($user)) {
            $user_ids = [];
            foreach ($user as $item) {
                $this->metrics->checkUserInstance($item);
                $user_ids[] = $item->id;
            }
            $this->metrics->whereIn($foreign_key, $user_ids);
            return $this;
        }

        $this->metricsModel->checkUserInstance($user);
        $this->metrics->where($foreign_key, $user->id);
        return $this;
    }

    /**
     * Get statistics Eloquent Builder for custom queries
     *
     * @return mixed
     */
    public function getBuilder()
    {
        return $this->metrics;
    }

    /**
     * Call magic methods
     * Example:
     *  - count calls Metrics::statsCount()
     *  - avg calls Metrics::statsAvg()
     *  - sum calls Metrics::statsSum()
     *  - min calls Metrics::statsMin()
     *  - max calls Metrics::statsMax()
     *
     * you can add yur own methods in Metrics child classes to get custom stats
     *
     * @param $name
     * @param $arguments
     * @return mixed
     */
    function __call($name, $arguments)
    {
        $methodName = ('stats' . strtoupper(substr($name, 0, 1)) . strtolower(substr($name, 1)));
        if (method_exists($this->metricsClass, $methodName)) {
            return call_user_func_array([$this->metricsClass, $methodName], [$this->metrics, $this->period]);
        }
    }
}