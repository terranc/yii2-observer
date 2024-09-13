<?php
namespace Terran\YiiObserver;

use yii\base\Event;
use yii\db\ActiveRecord;

trait ObserverTrait
{

    protected static $methodCache = [];
    protected $original = [];

    private static $EVENT_SAVING = 'saving';
    private static $EVENT_SAVED = 'saved';

    public function init()
    {
        parent::init();
        $this->on(ActiveRecord::EVENT_BEFORE_UPDATE, [$this, 'syncOriginal']);
        $this->on(ActiveRecord::EVENT_BEFORE_INSERT, [$this, 'syncOriginal']);

        $this->on(ActiveRecord::EVENT_BEFORE_UPDATE, function ($event) {
            $this->trigger(self::$EVENT_SAVING);
        });
        $this->on(ActiveRecord::EVENT_BEFORE_INSERT, function ($event) {
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
                if ($oldValue !== $this->$key) {
                    return true;
                }
            }
            return false;
        }
        $oldValue = isset($this->original[$attribute]) ? $this->original[$attribute] : null;
        return $oldValue !== $this->$attribute;
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
    public static function observe($observerClass)
    {
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
        $instance = new static;
        if (method_exists($instance, 'softDelete')) {
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
        $observer = new $observerClass();
        foreach (self::$methodCache[$observerClass] as $method) {
            if (array_key_exists($method, $eventsMap)) {
                Event::on($name, $eventsMap[$method], function ($event) use ($observer, $method) {
                    $observer->{$method}($event->sender);
                });
            }
        }
    }
}
