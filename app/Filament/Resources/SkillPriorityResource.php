<?php

namespace App\Filament\Resources;

use App\Exports\CustomExport\CustomSkillsPriorityExport;
use App\Filament\Resources\SkillPriorityResource\Pages;
use App\Models\District;
use App\Models\Province;
use App\Models\QualificationTitle;
use App\Models\SkillPriority;
use App\Models\Status;
use App\Models\TrainingProgram;
use App\Services\NotificationHandler;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Resources\Resource;
use Filament\Support\Enums\MaxWidth;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;
use PhpParser\Node\Stmt\Label;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class SkillPriorityResource extends Resource
{
    protected static ?string $model = SkillPriority::class;

    protected static ?string $navigationIcon = 'heroicon-o-light-bulb';

    protected static ?string $navigationGroup = "TARGET DATA INPUT";

    protected static ?string $navigationLabel = "Skill Priorities";

    protected static ?int $navigationSort = 7;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('province_id')
                    ->label('Province')
                    ->required()
                    ->markAsRequired(false)
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->options(function () {
                        return Province::whereNot('name', 'Not Applicable')
                            ->pluck('name', 'id')
                            ->toArray() ?: ['no_province' => 'No provinces available'];
                    })
                    ->disableOptionWhen(fn($value) => $value === 'no_province'),

                Select::make('district_id')
                    ->label('District')
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->options(function ($get) {
                        $provinceId = $get('province_id');
                        if ($provinceId) {
                            $districts = District::whereNot('name', 'Not Applicable')
                                ->where('province_id', $provinceId)
                                ->get();

                            return $districts->mapWithKeys(function ($district) {
                                $municipalityName = $district->underMunicipality->name ?? '';
                                $concatenatedName = $district->name . ($municipalityName ? " - $municipalityName" : '');
                                return [$district->id => $concatenatedName];
                            })->toArray() ?: ['no_district' => 'No district available'];
                        }
                        return ['no_district' => 'No distict available. Select a province first.'];
                    })
                    ->disableOptionWhen(fn($value) => $value === 'no_district'),

                Select::make('qualification_title_id')
                    ->label('Qualification Titles')
                    ->required()
                    ->relationship('trainingProgram')
                    ->markAsRequired(false)
                    ->searchable()
                    ->multiple()
                    ->preload()
                    ->native(false)
                    ->options(function () {
                        $qualificationTitles = TrainingProgram::all();

                        return $qualificationTitles->mapWithKeys(function ($title) {
                            $concatenatedName = $title->soc_code . " - " . $title->title;
                            return [$title->id => $concatenatedName];
                        })->toArray() ?: ['no_qualification_title' => 'No qualification title available'];
                    })
                    ->disableOptionWhen(fn($value) => $value === 'no_qualification_title')
                    ->afterStateUpdated(function ($state, $set) {
                        if ($state && isset($state[0])) {
                            $qualificationTitle = TrainingProgram::find($state[0]);

                            if ($qualificationTitle) {
                                $set('qualification_title', $qualificationTitle->title);
                            } else {
                                $set('qualification_title', null);
                            }
                        } else {
                            $set('qualification_title', null);
                        }
                    })
                    ->reactive(),

                TextInput::make('qualification_title')
                    ->label('Lot Name')
                    ->required()
                    ->markAsRequired(false),

                TextInput::make('available_slots')
                    ->label('Available Slots')
                    ->required()
                    ->markAsRequired(false)
                    ->integer()
                    ->disabled(fn($livewire) => $livewire->isEdit())
                    ->hidden(fn($livewire) => !$livewire->isEdit()),

                TextInput::make('total_slots')
                    ->label('Slots')
                    ->placeholder('Enter number of slots')
                    ->required()
                    ->markAsRequired(false)
                    ->numeric()
                    ->default(0)
                    ->minValue(0)
                    ->currencyMask(thousandSeparator: ',', decimalSeparator: '.', precision: 0)
                    ->dehydrated(),

                TextInput::make('year')
                    ->label('Year')
                    ->required()
                    ->markAsRequired(false)
                    ->autocomplete(false)
                    ->numeric()
                    ->default(date('Y'))
                    ->rules(['min:' . date('Y'), 'digits:4'])
                    ->validationAttribute('year')
                    ->validationMessages([
                        'min' => 'The allocation year must be at least ' . date('Y') . '.',
                    ]),
                
                Select::make('status_id')
                    ->relationship('status', 'desc')
                    ->required()
                    ->markAsRequired(false)
                    ->hidden(fn(Page $livewire) => $livewire instanceof CreateRecord)
                    ->default(1)
                    ->native(false)
                    ->options(function () {
                        return Status::all()
                            ->pluck('desc', 'id')
                            ->toArray() ?: ['no_status' => 'No status available'];
                    })
                    ->validationAttribute('status'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('provinces.name')
                    ->label('Province')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('district.name')
                    ->label('District')
                    ->getStateUsing(function ($record) {
                        if ($record->district) {
                            if ($record->district->underMunicipality) {
                                return $record->district->name . ' - ' . $record->district->underMunicipality->name;
                            } else {
                                return $record->district->name;
                            }
                        } else {
                            return '-';
                        }
                    }),

                TextColumn::make('qualification_title')
                    ->label('Lot Name')
                    ->searchable(),

                TextColumn::make('trainingProgram.title')
                    ->searchable()
                    ->label('SOC Title')
                    ->getStateUsing(function ($record) {
                        return $record->trainingProgram->implode('title', ', ');
                    }),

                TextColumn::make('total_slots')
                        ->label('Total Target Beneficiaries'),

                TextColumn::make('available_slots')
                    ->label('Available Target Beneficiaries'),

                TextColumn::make('year')
                    ->label('Year'),

                SelectColumn::make('status_id')
                    ->label('Status')
                    ->options([
                        '1' => 'Active',
                        '2' => 'Inactive',
                    ])
                    ->disablePlaceholderSelection()
                    ->extraAttributes(['style' => 'width: 125px;'])
            ])
            ->filters([
                TrashedFilter::make()
                    ->label('Records')
                    ->visible(fn() => Auth::user()->hasRole(['Super Admin', 'Admin']) || Auth::user()->can('filter skills priority')),
            ])
            ->actions([
                ActionGroup::make([
                    // Action::make('addSlots')
                    //     ->modalContent(function (SkillPriority $record): HtmlString {
                    //         return new HtmlString("
                    //             <div style='margin-bottom: 1rem; margin-top: 1rem; font-size: .9rem; display: grid; grid-template-columns: 1fr 2fr; gap: 10px;' >
                    //                 <div style='font-weight: bold;'>Province:</div>
                    //                 <div>{$record->provinces->name}</div>
                    //                 <div style='font-weight: bold;'>Training Program:</div>
                    //                 <div>{$record->trainingPrograms->title}</div>
                    //                 <div style='font-weight: bold;'>Available Slots:</div>
                    //                 <div>{$record->available_slots}</div>
                    //                 <div style='font-weight: bold;'>Total Slots:</div>
                    //                 <div>{$record->total_slots}</div>
                    //                 <div style='font-weight: bold;'>Year:</div>
                    //                 <div>{$record->year}</div>
                    //             </div>
                    //         ");
                    //     })
                    //     ->modalHeading('Add Slots')
                    //     ->modalWidth(MaxWidth::TwoExtraLarge)
                    //     ->icon('heroicon-o-plus')
                    //     ->label('Add Slots')
                    //     ->form([
                    //         TextInput::make('available_slots')
                    //             ->label('Add Slots')
                    //             ->autocomplete(false)
                    //             ->integer()
                    //             ->default(0)
                    //             ->minValue(0)
                    //     ])
                    //     ->action(function (array $data, SkillPriority $record): void {
                    //         $record->available_slots += $data['available_slots'];
                    //         $record->total_slots += $data['available_slots'];
                    //         $record->save();
                    //         NotificationHandler::sendSuccessNotification('Saved', 'Skill priority slots have been added successfully.');
                    //     }),
                    EditAction::make()
                        ->hidden(fn($record) => $record->trashed()),
                    DeleteAction::make()
                        ->action(function ($record, $data) {
                            $record->delete();

                            NotificationHandler::sendSuccessNotification('Deleted', 'Allocation has been deleted successfully.');
                        }),
                    RestoreAction::make()
                        ->action(function ($record, $data) {
                            $record->restore();

                            NotificationHandler::sendSuccessNotification('Restored', 'Allocation has been restored successfully.');
                        }),
                    ForceDeleteAction::make()
                        ->action(function ($record, $data) {
                            $record->forceDelete();

                            NotificationHandler::sendSuccessNotification('Force Deleted', 'Allocation has been deleted permanently.');
                        }),
                ])
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->action(function ($records) {
                            $records->each->delete();

                            NotificationHandler::sendSuccessNotification('Deleted', 'Selected Skills Priority have been deleted successfully.');
                        })
                        ->visible(fn() => Auth::user()->hasRole(['Super Admin', 'Admin']) || Auth::user()->can('delete skills priority')),
                    RestoreBulkAction::make()
                        ->action(function ($records) {
                            $records->each->restore();

                            NotificationHandler::sendSuccessNotification('Restored', 'Selected Skills Priority have been restored successfully.');
                        })
                        ->visible(fn() => Auth::user()->hasRole('Super Admin') || Auth::user()->can('restore skills priority')),
                    ForceDeleteBulkAction::make()
                        ->action(function ($records) {
                            $records->each->forceDelete();

                            NotificationHandler::sendSuccessNotification('Force Deleted', 'Selected Skills Priority have been deleted permanently.');
                        })
                        ->visible(fn() => Auth::user()->hasRole('Super Admin') || Auth::user()->can('force delete skills priority')),
                    ExportBulkAction::make()
                        ->exports([
                            CustomSkillsPriorityExport::make()
                                ->withColumns([
                                    Column::make('provinces.name')
                                        ->heading('Province'),
                                    Column::make('trainingPrograms.title')
                                        ->heading('Training Program'),
                                    Column::make('available_slots')
                                        ->heading('Available Slots'),
                                    Column::make('total_slots')
                                        ->heading('Total Slots'),
                                    Column::make('year')
                                        ->heading('Year'),
                                ])
                                ->withFilename(date('Y-m-d') . '-skill_priority_export.xlsx'),
                        ])
                ])
                ->label('Select Action'),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSkillPriorities::route('/'),
            'create' => Pages\CreateSkillPriority::route('/create'),
            'edit' => Pages\EditSkillPriority::route('/{record}/edit'),
        ];
    }

}
