<?php namespace GurmanAlexander\Metrics\Types;

use Carbon\Carbon;
use GurmanAlexander\Metrics\Metrics;
use Illuminate\Support\Facades\Log;

class TimeMetrics extends Metrics
{

    /**
     * For time-metrics we just need user or|and admin
     * User  - The user to which the metric belongs.
     * Admin - The user who called the metric.
     *
     * TimeMetrics constructor.
     * @param null $user
     * @param null $admin
     */
    public function __construct($user = null, $admin = null)
    {
        $this->userModel = $this->checkUserInstance($user);
        $this->adminModel = $this->checkUserInstance($admin);

        parent::__construct([]);
    }

    public function addMetrics($metricable)
    {
        $metrics = $this->findCurrentMetrics($metricable);

        if ($metrics) return false;

        $metrics = $this;
        $metrics->type = static::class;
        $metrics->metricable()->associate($metricable);

        $metrics->fired();

        $metrics->fillUsers($metrics);

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
        $metrics->count = $metrics->end_at->diffInSeconds($metrics->start_at);
        $metrics->metricable()->associate($metricable);

        $metrics->fired();

        $metrics->fillUsers($this);

        return $metrics->save();
    }

    public function onceMetrics($metricable)
    {
        return false;
    }
}