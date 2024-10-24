<?php

namespace App\Filament\Resources;

use App\Models\Legislator;
use App\Models\Particular;
use App\Models\Status;
use App\Filament\Resources\LegislatorResource\Pages;
use App\Services\NotificationHandler;
use Filament\Resources\Resource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Pages\Page;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Columns\SelectColumn;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LegislatorResource extends Resource
{
    protected static ?string $model = Legislator::class;

    protected static ?string $navigationGroup = "TARGET DATA INPUT";

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make("name")
                    ->label('Legislator')
                    ->placeholder(placeholder: 'Enter legislator name')
                    ->required()
                    ->markAsRequired(false)
                    ->autocomplete(false)
                    ->validationAttribute('Legislator'),

                Select::make("particular")
                    ->relationship("particular", "name")
                    ->required()
                    ->markAsRequired(false)
                    ->searchable()
                    ->preload()
                    ->multiple()
                    ->native(false)
                    ->options(fn() => self::getParticularOptions())
                    ->disableOptionWhen(fn($value) => $value === 'no_particular'),

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
                            ->toArray() ?: ['no_status' => 'No Status Available'];
                    }),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading('no legislators available')
            ->columns([
                TextColumn::make("name")
                    ->sortable()
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('particular_name')
                    ->label('Particular')
                    ->toggleable()
                    ->getStateUsing(fn($record) => self::getParticularNames($record))
                    ->html(),

                SelectColumn::make('status_id')
                    ->label('Status')
                    ->options([
                        '1' => 'Active',
                        '2' => 'Inactive',
                    ])
                    ->disablePlaceholderSelection()
            ])
            ->filters([
                TrashedFilter::make()
                    ->label('Records'),

                SelectFilter::make('status')
                    ->label('Status')
                    ->relationship('status', 'desc'),
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make()
                        ->hidden(fn($record) => $record->trashed()),
                    DeleteAction::make()
                        ->action(function ($record, $data) {
                            $record->delete();

                            NotificationHandler::sendSuccessNotification('Deleted', 'Legislator has been deleted successfully.');
                        }),
                    RestoreAction::make()
                        ->action(function ($record, $data) {
                            $record->restore();

                            NotificationHandler::sendSuccessNotification('Restored', 'Legislator has been restored successfully.');
                        }),
                    ForceDeleteAction::make()
                        ->action(function ($record, $data) {
                            $record->forceDelete();

                            NotificationHandler::sendSuccessNotification('Force Deleted', 'Legislator has been deleted permanently.');
                        }),
                ])
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->action(function ($records) {
                            $records->each->delete();

                            NotificationHandler::sendSuccessNotification('Deleted', 'Selected legislators have been deleted successfully.');
                        }),
                    RestoreBulkAction::make()
                        ->action(function ($records) {
                            $records->each->restore();

                            NotificationHandler::sendSuccessNotification('Restored', 'Selected legislators have been restored successfully.');
                        }),
                    ForceDeleteBulkAction::make()
                        ->action(function ($records) {
                            $records->each->forceDelete();

                            NotificationHandler::sendSuccessNotification('Force Deleted', 'Selected legislators have been deleted permanently.');
                        }),
                    ExportBulkAction::make()
                        ->exports([
                            ExcelExport::make()
                                ->withColumns([
                                    Column::make('name')
                                        ->heading('Legislator'),
                                    Column::make('particular.name')
                                        ->heading('Particular')
                                        ->getStateUsing(function ($record) {
                                            if (!$record->particular) {
                                                return 'No Particular Available';
                                            }

                                            return $record->particular->map(function ($particular) {
                                                $district = $particular->district;
                                                $municipality = $district ? $district->municipality : null;

                                                $subParticular = $particular->subParticular ? $particular->subParticular->name : null;
                                                $formattedName = '';

                                                if (in_array($subParticular, ['Senator', 'House Speaker', 'House Speaker (LAKAS)'])) {
                                                    $formattedName = "{$subParticular}";
                                                } elseif ($subParticular === 'Party-list') {
                                                    $formattedName = "{$subParticular} - {$particular->partylist->name}";
                                                } else {
                                                    $districtName = $district ? $district->name : '';
                                                    $municipalityName = $municipality ? $municipality->name : '';
                                                    $province = $municipality ? $municipality->province : null;
                                                    $provinceName = $province ? $province->name : '';

                                                    $formattedName = "{$subParticular} - {$districtName}, {$municipalityName}, {$provinceName}";
                                                }

                                                return trim($formattedName, ', ');
                                            })->implode(', ');
                                        })
                                ])
                                ->withFilename(date('m-d-Y') . ' - Legislator'),
                        ]),
                ]),
            ]);
    }

    protected static function getParticularOptions(): array
    {
        return Particular::query()
            ->with('district')
            ->get()
            ->mapWithKeys(fn($item) => self::formatParticular($item))
            ->toArray() ?: ['no_particular' => 'No Particular Available'];
    }

    protected static function formatParticular($item): array
    {
        $subParticular = $item->subParticular->name;

        if ($subParticular === 'Senator' || $subParticular === 'House Speaker' || $subParticular === 'House Speaker (LAKAS)') {
            $formattedName = "{$item->subParticular->name}";
        } elseif ($subParticular === 'Party-list') {
            $formattedName = "{$item->subParticular->name} - {$item->partylist->name}";
        } else {
            $formattedName = "{$item->subParticular->name} - {$item->district->name}, {$item->district->municipality->name}";
        }

        return [$item->id => $formattedName];
    }

    protected static function getParticularNames($record): string
    {
        return $record->particular->map(function ($particular, $index) use ($record) {
            $municipalityName = $particular->district->name . ', ' . $particular->district->municipality->name;

            $paddingTop = ($index > 0) ? 'padding-top: 15px;' : '';

            $comma = ($index < $record->particular->count() - 1) ? ',' : '';

            if ($particular->subParticular->name === 'Party-list') {
                return '<div style="' . $paddingTop . '">' . $particular->subParticular->name . ' - ' . $particular->partylist->name . $comma . '</div>';
            } elseif (in_array($particular->subParticular->name, ['Senator', 'House Speaker', 'House Speaker (LAKAS)'])) {
                return '<div style="' . $paddingTop . '">' . $particular->subParticular->name . $comma . '</div>';
            } else {
                return '<div style="' . $paddingTop . '">' . $particular->subParticular->name . ' - ' . $municipalityName . $comma . '</div>';
            }
        })->implode('');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->whereNotIn('name', ['Regional Office', 'Central Office']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLegislators::route('/'),
            'create' => Pages\CreateLegislator::route('/create'),
            'edit' => Pages\EditLegislator::route('/{record}/edit'),
        ];
    }
}
