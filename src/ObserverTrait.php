<?php
namespace Terran\YiiObserver;

use yii\base\Event;
use yii\db\ActiveRecord;

trait ObserverTrait
{
    public $originalAttributes = [];

    public function init()
    {
        parent::init();
        $this->on(ActiveRecord::EVENT_BEFORE_UPDATE, [$this, 'recordOriginalAttributes']);
        $this->on(ActiveRecord::EVENT_BEFORE_INSERT, [$this, 'recordOriginalAttributes']);
    }
    public function isDirty($attribute)
    {
        $oldValue = isset($this->originalAttributes[$attribute]) ? $this->originalAttributes[$attribute] : null;
        return $oldValue !== $this->$attribute;
    }

    public function recordOriginalAttributes()
    {
        $this->originalAttributes = $this->getOldAttributes();
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
        ];
        $observer = new $observerClass();
        $reflection = new \ReflectionClass($observerClass);

        foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if (array_key_exists($method->name, $eventsMap)) {
                Event::on(self::class, $eventsMap[$method->name], function ($event) use ($observer, $method) {
                    $observer->{$method->name}($event->sender);
                });
            }
        }
    }
}
