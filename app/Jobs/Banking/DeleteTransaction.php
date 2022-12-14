<?php

namespace App\Jobs\Banking;

use App\Abstracts\Job;
use App\Interfaces\Job\ShouldDelete;

class DeleteTransaction extends Job implements ShouldDelete
{
    public function handle(): bool
    {
        $this->authorize();

        \DB::transaction(function () {
            $this->model->recurring()->delete();
            $this->model->delete();
        });

        return true;
    }

    /**
     * Determine if this action is applicable.
     */
    public function authorize(): void
    {
        if ($this->model->reconciled) {
            $message = trans('messages.warning.reconciled_tran');

            throw new \Exception($message);
        }

        if ($this->model->isTransferTransaction()) {
            throw new \Exception('Unauthorized');
        }
    }
}
