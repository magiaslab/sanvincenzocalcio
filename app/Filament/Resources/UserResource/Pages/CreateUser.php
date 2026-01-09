<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Mail\ParentCredentialsMail;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected ?string $plainPassword = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Se l'utente ha il ruolo "genitore" e non è stata fornita una password, generane una
        if (isset($data['roles']) && in_array('genitore', $data['roles'])) {
            if (empty($data['password'])) {
                // Genera password e salvala temporaneamente per l'email
                $plainPassword = Str::random(12);
                $this->plainPassword = $plainPassword;
                $data['password'] = Hash::make($plainPassword);
            } else {
                // Se viene fornita una password, dobbiamo recuperarla prima che venga hashata
                // Ma a questo punto è già hashata dal form, quindi non possiamo recuperarla
                // Generiamo una nuova password temporanea
                $plainPassword = Str::random(12);
                $this->plainPassword = $plainPassword;
                $data['password'] = Hash::make($plainPassword);
            }
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $user = $this->record;
        
        // Se l'utente è un genitore, invia le credenziali via email
        if ($user->hasRole('genitore')) {
            $plainPassword = $this->plainPassword ?? Str::random(12);
            
            // Se non abbiamo la password in chiaro, generiamone una nuova
            if (!$this->plainPassword) {
                $plainPassword = Str::random(12);
                $user->password = \Illuminate\Support\Facades\Hash::make($plainPassword);
                $user->save();
            }

            try {
                $loginUrl = url('/admin/login');
                
                Mail::to($user->email)->send(
                    new ParentCredentialsMail($user, $plainPassword, $loginUrl)
                );

                Notification::make()
                    ->success()
                    ->title('Utente creato con successo')
                    ->body("Le credenziali sono state inviate via email a {$user->email}. Password temporanea: {$plainPassword}")
                    ->persistent()
                    ->send();
            } catch (\Exception $e) {
                // Se l'invio email fallisce, mostra comunque le credenziali
                Notification::make()
                    ->warning()
                    ->title('Utente creato')
                    ->body("Errore nell'invio email: {$e->getMessage()}. Credenziali: Email: {$user->email}, Password: {$plainPassword}")
                    ->persistent()
                    ->send();
            }
        }
    }
}
