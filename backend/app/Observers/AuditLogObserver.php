<?php

namespace App\Observers;

use App\Domain\Admin\AdminActivityLogger;
use Illuminate\Database\Eloquent\Model;

class AuditLogObserver
{
    public function __construct(
        protected AdminActivityLogger $logger
    ) {
    }

    public function created(Model $model): void
    {
        $this->logAction($model, 'created', 'Utworzono ' . $this->modelName($model));
    }

    public function updated(Model $model): void
    {
        if (!$model->isDirty()) {
            return;
        }

        $dirty = $model->getDirty();
        
        // Exclude passwords/tokens/metadata that are sensitive or too large
        $exclude = ['password', 'remember_token', 'password_hash', 'two_factor_secret', 'two_factor_recovery_codes'];
        $cleanDirty = array_diff_key($dirty, array_flip($exclude));
        
        if (empty($cleanDirty)) {
            return;
        }

        $oldValues = [];
        $newValues = [];

        foreach (array_keys($cleanDirty) as $key) {
            $oldValues[$key] = $model->getOriginal($key);
            $newValues[$key] = $model->getAttribute($key);
        }

        // Limit the summary list of fields to avoid extremely long texts
        $changedFields = implode(', ', array_slice(array_keys($cleanDirty), 0, 5));
        if (count($cleanDirty) > 5) {
            $changedFields .= ' i ' . (count($cleanDirty) - 5) . ' innych';
        }

        $this->logAction($model, 'updated', 'Zaktualizowano ' . $this->modelName($model) . ' (zmieniono: ' . $changedFields . ')', $oldValues, $newValues);
    }

    public function deleted(Model $model): void
    {
        $isSoftDelete = method_exists($model, 'isForceDeleting') && !$model->isForceDeleting();
        $event = $isSoftDelete ? 'soft_deleted' : 'deleted';
        $action = $isSoftDelete ? 'Skasowano (soft-delete)' : 'Trwale usunięto';

        $this->logAction($model, $event, $action . ' ' . $this->modelName($model));
    }

    public function restored(Model $model): void
    {
        $this->logAction($model, 'restored', 'Przywrócono ' . $this->modelName($model));
    }

    public function forceDeleted(Model $model): void
    {
        $this->logAction($model, 'force_deleted', 'Trwale usunięto ' . $this->modelName($model));
    }

    protected function logAction(Model $model, string $event, string $summary, ?array $oldValues = null, ?array $newValues = null): void
    {
        // Don't log activity logs themselves
        if ($model instanceof \App\Models\AdminActivityLog) {
            return;
        }

        $metadata = [
            'source' => app()->runningInConsole() ? 'cli' : 'web',
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ];

        $this->logger->log(
            subject: $model,
            event: $event,
            summary: $summary,
            oldValues: $oldValues,
            newValues: $newValues,
            metadata: $metadata
        );
    }

    protected function modelName(Model $model): string
    {
        $class = class_basename($model);
        
        // If model has a name or title attribute, append it for better summary readability
        $nameAttr = '';
        if (isset($model->name)) {
            $val = $model->name;
            $nameAttr = ' "' . (is_array($val) ? ($val['pl'] ?? reset($val)) : (string) $val) . '"';
        } elseif (isset($model->title)) {
            $val = $model->title;
            $nameAttr = ' "' . (is_array($val) ? ($val['pl'] ?? reset($val)) : (string) $val) . '"';
        } elseif (isset($model->number)) {
            $nameAttr = ' "' . $model->number . '"';
        } elseif (isset($model->code)) {
            $nameAttr = ' "' . $model->code . '"';
        }

        return $class . ' #' . $model->getKey() . $nameAttr;
    }
}
