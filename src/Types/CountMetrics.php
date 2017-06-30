<?php namespace GurmanAlexander\Metrics\Types;

use Carbon\Carbon;
use GurmanAlexander\Metrics\Metrics;
use Illuminate\Support\Facades\Log;

class CountMetrics extends Metrics
{

    protected $countMetricsCount;

    /**
     * For time-metrics we just need user or|and admin
     * User  - The user to which the metric belongs.
     * Admin - The user who called the metric.
     * count - How much to increase the metric. For example, you can use money metric
     *
     * CountMetrics constructor.
     * @param null $user
     * @param null $admin
     * @param int $count
     */
    public function __construct($user = null, $admin = null, $count = 1)
    {
        $this->userModel = $this->checkUserInstance($user);
        $this->adminModel = $this->checkUserInstance($admin);

        $this->countMetricsCount = $count;

        parent::__construct([]);
    }

    public function addMetrics($metricable)
    {
        $metrics = $this->findCurrentMetrics($metricable);

        if (!$metrics) $metrics = $this;

        $metrics->type = static::class;
        $metrics->count+= $this->countMetricsCount;

        $metrics->fired();

        $metrics->metricable()->associate($metricable);

        $metrics->fillUsers($this);

        return $metrics->save();
    }

    public function closeMetrics($metricable)
    {
        $metrics = $this->findCurrentMetrics($metricable);

        if (!$metrics) {
            Log::error('trying to close unstarted metrics');
            return false;
        }

        $metrics->type = static::class;
        $metrics->end_at = Carbon::now();
        $metrics->metricable()->associate($metricable);

        $metrics->fired();

        $metrics->fillUsers($this);

        return $metrics->save();
    }

    public function onceMetrics($metricable)
    {
        $this->addMetrics($metricable);
        return $this->closeMetrics($metricable);
    }
}