<?php namespace GurmanAlexander\Metrics;

use Carbon\Carbon;
use GurmanAlexander\Metrics\Exceptions\NotUserClassException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

abstract class Metrics extends Model
{

    public $incrementing = false;

    protected $table = 'metrics';

    public $timestamps = false;

    protected $fillable = [
        'count',
        'data',
        'is_failed',
        'type',
        'metricable_id',
        'metricable_type',
        'start_at',
        'end_at',
    ];

    protected $dates = [
        'start_at',
        'end_at',
    ];

    protected $casts = [
        'data'      => 'array',
        'is_failed' => 'boolean',
        'count'     => 'integer',
        'type'      => 'string',
    ];

    protected $guarded = [];

    protected $userModel;
    protected $adminModel;

    public abstract function addMetrics($metricable);
    public abstract function onceMetrics($metricable);
    public abstract function closeMetrics($metricable);

    public function __construct(array $attributes = [])
    {
        $this->id = Uuid::uuid4()->toString();
        parent::__construct($attributes);
    }

    public function user()
    {
        return $this->belongsTo(Config::get('metrics.user', '\App\User'),'user_id');
    }

    public function admin()
    {
        return $this->belongsTo(Config::get('metrics.user', '\App\User'),'admin_id');
    }

    public function metricable()
    {
        return $this->morphTo();
    }

    /**
     * Find opened metrics if exists
     *
     * @param $metricable
     * @return Metrics|null - child class
     */
    protected function findCurrentMetrics($metricable)
    {
        $metrics = static::where('metricable_id', $metricable->id)
            ->where('metricable_type', get_class($metricable))
            ->where('type', static::class)
            ->whereNull('end_at')
        ;
        if (!empty($this->userModel)) {
            $metrics->where('user_id', $this->userModel->id);
        }
        return $metrics->first();
    }

    /**
     * Check if $user instance of User Model
     *
     * @param $user
     * @return mixed - User Model
     */
    public function checkUserInstance($user)
    {
        if ($user && get_class($user) !== Config::get('metrics.user')) {
            throw new NotUserClassException(
                'Argument passed to ' . static::class .
                ' must be an instance of ' . Config::get('metrics.user') .
                ', instance of ' . get_class($user) . ' given, called in ' . __FILE__ .
                ' on line ' . __LINE__ . ' and defined'
            );
        }
        return $user;
    }

    /**
     * Write history of metrics to data
     */
    protected function fired()
    {
        $data = $this->data;
        $data['fired_at'][] = Carbon::now()->toDateTimeString();
        $this->data = $data;
    }

    /**
     * Write user and admin (if exists) to metrics
     * Write user activity (history) to data:
     * [
     *      'user'  => [user_id => [...dates]],
     *      'admin' => [admin_id => [...dates]],
     * ]
     *
     * @param Metrics $metrics
     */
    protected function fillUsers(Metrics &$metrics)
    {

        foreach (['user', 'admin'] as $userType) {
            if ($metrics->{$userType . 'Model'}) {
                if (!$metrics->{$userType}) {
                    $metrics->{$userType}()->associate($metrics->{$userType . 'Model'});
                }
            }
            if (!empty($metrics->{$userType})) {
                $data = $this->data;
                if (empty($data[$userType])) {
                    $data[$userType] = [];
                }
                if (empty($data[$userType][$metrics->{$userType}->id])) {
                    $data[$userType][$metrics->{$userType}->id] = [];
                }
                $data[$userType][$metrics->{$userType}->id][] = Carbon::now()->toDateTimeString();
                $this->data = $data;
            }
        }

    }

    /**
     * Build period selectors for SQL query to get stats data for needed period
     * For week example:
     *  'week' as `period`,
     *  WEEK(`end_at`, 1) as `week`
     *
     * @param $period
     * @return array
     */
    protected static function getPeriodSqlColumns($period)
    {
        $result = [
            DB::raw('"' . $period . '" as period')
        ];
        switch ($period) {
            case 'week': {
                $result[] = DB::raw(strtoupper($period) . '(end_at, 1) as ' . $period);
            }break;
            default: {
                $result[] = DB::raw(strtoupper($period) . '(end_at) as ' . $period);
            }
        }
        return $result;
    }

    /**
     * Build complete SQL select
     *
     * @param Builder $builder
     * @param $period
     * @param $raw
     * @return Builder
     */
    protected static function statsBuilder(Builder $builder, $period, $raw)
    {
        return $builder->select(
            array_merge([
                $raw,
                DB::raw('DATE_FORMAT(end_at, "%Y-%m-%d") as date'),
            ], self::getPeriodSqlColumns($period))
        );
    }

    /**
     * Get COUNT stats for Metrics divided into periods
     *
     * @param Builder $builder
     * @param $period
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function statsCount(Builder $builder, $period)
    {
        return self::statsBuilder($builder, $period, DB::raw('COUNT(*) as count'))->get();
    }

    /**
     * Get AVG stats for Metrics divided into periods
     *
     * @param Builder $builder
     * @param $period
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function statsAvg(Builder $builder, $period)
    {
        return self::statsBuilder($builder, $period, DB::raw('AVG(count) as avg'))->get();
    }

    /**
     * Get SUM stats for Metrics divided into periods
     *
     * @param Builder $builder
     * @param $period
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function statsSum(Builder $builder, $period)
    {
        return self::statsBuilder($builder, $period, DB::raw('SUM(count) as sum'))->get();
    }

    /**
     * Get MIN stats for Metrics divided into periods
     *
     * @param Builder $builder
     * @param $period
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function statsMin(Builder $builder, $period)
    {
        return self::statsBuilder($builder, $period, DB::raw('MIN(count) as min'))->get();
    }

    /**
     * Get MAX stats for Metrics divided into periods
     *
     * @param Builder $builder
     * @param $period
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function statsMax(Builder $builder, $period)
    {
        return self::statsBuilder($builder, $period, DB::raw('MAX(count) as max'))->get();
    }
}