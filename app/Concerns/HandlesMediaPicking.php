<?php

namespace App\Concerns;

trait HandlesMediaPicking
{
    /**
     * Called from the media picker modal when an image is clicked.
     * Updates the bound form-state key via form->fill() so FileUpload hydration runs cleanly,
     * then closes the modal.
     */
    public function pickMediaToState(string $statePath, string $path): void
    {
        // 'data.image_path' -> 'image_path'
        $key = preg_replace('/^data\./', '', $statePath);

        if (property_exists($this, 'data') && is_array($this->data)) {
            // Merge into existing data, then re-fill through the form so any
            // component (e.g. FileUpload) does its proper hydration on the new value.
            $merged = array_merge($this->data, [$key => $path]);
            if (method_exists($this, 'form')) {
                try {
                    $this->form->fill($merged);
                } catch (\Throwable $e) {
                    // Fallback: direct assignment
                    $this->data[$key] = $path;
                }
            } else {
                $this->data[$key] = $path;
            }
        }

        // Send a flash notification so the user has visible feedback
        try {
            \Filament\Notifications\Notification::make()
                ->title('Picked: ' . basename($path))
                ->success()
                ->send();
        } catch (\Throwable $e) {}

        $this->dispatch('close-modal');
    }
}
