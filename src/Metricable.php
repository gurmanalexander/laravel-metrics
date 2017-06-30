<?php namespace GurmanAlexander\Metrics;

trait Metricable
{

    /**
     * Start watching metrics
     *
     * @param Metrics $metrics
     * @return boolean
     */
    public function startMetrics(Metrics $metrics)
    {
        return $this->addMetrics($metrics);
    }

    /**
     * Fire once metrics
     *
     * @param Metrics $metrics
     * @return boolean
     */
    public function onceMetrics(Metrics $metrics)
    {
        return $metrics->onceMetrics($metrics);
    }

    /**
     * Add or start metrics to existing metrics
     *
     * @param Metrics $metrics
     * @return boolean
     */
    public function addMetrics(Metrics $metrics)
    {
        return $metrics->addMetrics($this);
    }

    /**
     * Stop watching metrics
     *
     * @param Metrics $metrics
     * @return boolean
     */
    public function closeMetrics(Metrics $metrics)
    {
        return $metrics->closeMetrics($this);
    }
}