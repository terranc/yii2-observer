<?php
namespace Terran\YiiObserver;

use yii\base\Event;
use yii\db\ActiveRecord;

trait ObserverTrait
{

    protected static $methodCache = [];
    protected $original = [];
    /** @var bool 是否使用全等比較檢測字段變動，默認為弱比較 */
    protected static $observerStrictDirtyComparison = false;

    private static $EVENT_SAVING = 'saving';
    private static $EVENT_SAVED = 'saved';

    public function init()
    {
        parent::init();
        $this->on(ActiveRecord::EVENT_BEFORE_UPDATE, function ($event) {
            $this->syncOriginal();
            $this->trigger(self::$EVENT_SAVING);
        });

        $this->on(ActiveRecord::EVENT_BEFORE_INSERT, function ($event) {
            $this->syncOriginal();
            $this->trigger(self::$EVENT_SAVING);
        });

        $this->on(ActiveRecord::EVENT_AFTER_UPDATE, function ($event) {
            $this->trigger(self::$EVENT_SAVED);
        });
        $this->on(ActiveRecord::EVENT_AFTER_INSERT, function ($event) {
            $this->trigger(self::$EVENT_SAVED);
        });
    }
    public function isDirty($attribute = null)
    {
        if ($attribute === null) {
            foreach ($this->original as $key => $oldValue) {
                if ($this->observerValueHasChanged($oldValue, $this->$key)) {
                    return true;
                }
            }
            return false;
        }
        $oldValue = isset($this->original[$attribute]) ? $this->original[$attribute] : null;
        return $this->observerValueHasChanged($oldValue, $this->$attribute);
    }
    public function getOriginal($attribute = null, $default = null)
    {
        if ($attribute === null) {
            return $this->original;
        }
        return isset($this->original[$attribute]) ? $this->original[$attribute] : $default;
    }

    public function syncOriginal()
    {
        $this->original = $this->getOldAttributes();
    }
    /**
     * @param mixed $oldValue 原始值
     * @param mixed $newValue 新值
     * @return bool 返回是否视为变化
     */
    protected function observerValueHasChanged($oldValue, $newValue)
    {
        if (static::$observerStrictDirtyComparison) {
            return $oldValue !== $newValue;
        }

        if ($this->observerNumericEquals($oldValue, $newValue)) {
            return false;
        }

        return $oldValue !== $newValue;
    }
    /**
     * @param mixed $oldValue 原始值
     * @param mixed $newValue 新值
     * @return bool 返回数值上是否相等
     */
    protected function observerNumericEquals($oldValue, $newValue)
    {
        if (!is_numeric($oldValue) || !is_numeric($newValue)) {
            return false;
        }

        return $this->observerNormalizeNumeric($oldValue) == $this->observerNormalizeNumeric($newValue);
    }

    /**
     * @param mixed $value 原始值
     * @return float|int 归一化后的数值
     */
    protected function observerNormalizeNumeric($value)
    {
        return $value + 0;
    }
    /**
     * @param string $observerClass 監聽類名
     * @param bool $useStrictComparison 是否啟用全等比較，默認使用弱比較
     */
    public static function observe($observerClass, $useStrictComparison = false)
    {
        static::$observerStrictDirtyComparison = (bool)$useStrictComparison;

        $eventsMap = [
            'creating' => self::EVENT_BEFORE_INSERT,
            'created' => self::EVENT_AFTER_INSERT,
            'updating' => self::EVENT_BEFORE_UPDATE,
            'updated' => self::EVENT_AFTER_UPDATE,
            'deleting' => self::EVENT_BEFORE_DELETE,
            'deleted' => self::EVENT_AFTER_DELETE,
            'saving' => self::$EVENT_SAVING,
            'saved' => self::$EVENT_SAVED,
        ];
        // Can be used with yiithings/yii2-softdelete
        if (method_exists(static::class, 'softDelete')) {
            $eventsMap['deleting'] = 'beforeSoftDelete';
            $eventsMap['deleted'] = 'afterSoftDelete';
            $eventsMap['forceDeleted'] = 'afterForceDelete';
            $eventsMap['restoring'] = 'beforeRestore';
            $eventsMap['restored'] = 'afterRestore';
        }
        // Check if there's cache, if not then get and cache the public methods of the class
        if (!isset(self::$methodCache[$observerClass])) {
            self::$methodCache[$observerClass] = get_class_methods($observerClass);
        }

        $name = static::class;
        $observer = null;

        foreach (self::$methodCache[$observerClass] as $method) {
            if (array_key_exists($method, $eventsMap)) {
                // 只在找到第一个有效的监听方法时实例化
                if ($observer === null) {
                    $observer = new $observerClass();
                }
                Event::on($name, $eventsMap[$method], function ($event) use ($observer, $method) {
                    $observer->{$method}($event->sender);
                });
            }
        }
    }
}
