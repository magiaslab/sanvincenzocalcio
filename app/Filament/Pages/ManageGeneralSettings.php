<?php

namespace App\Filament\Pages;

use App\Settings\GeneralSettings;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Notifications\Notification;

class ManageGeneralSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationLabel = 'Impostazioni Generali';
    protected static ?string $title = 'Impostazioni Generali';
    protected static string $view = 'filament.pages.manage-general-settings';

    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('super_admin');
    }

    public ?array $data = [];

    public function mount(GeneralSettings $settings): void
    {
        $this->form->fill([
            'site_name' => $settings->site_name,
            'site_logo' => $settings->site_logo,
            'primary_color' => $settings->primary_color,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('site_name')
                    ->label('Nome Società')
                    ->required(),
                Forms\Components\FileUpload::make('site_logo')
                    ->label('Logo')
                    ->image()
                    ->directory('logos'),
                Forms\Components\ColorPicker::make('primary_color')
                    ->label('Colore Primario')
                    ->required(),
            ])
            ->statePath('data');
    }

    public function save(GeneralSettings $settings): void
    {
        $data = $this->form->getState();

        $settings->site_name = $data['site_name'];
        $settings->site_logo = $data['site_logo'];
        $settings->primary_color = $data['primary_color'];

        $settings->save();

        Notification::make()
            ->success()
            ->title('Impostazioni salvate')
            ->send();
    }
}
