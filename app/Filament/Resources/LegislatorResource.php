<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LegislatorResource\Pages;
use App\Models\Legislator;
use App\Models\Particular;
use App\Models\Status;
use App\Services\NotificationHandler;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Resource;
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
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Exports\CustomExport\CustomLegislatorExport;
use Illuminate\Support\Facades\Auth;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

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
                    ->placeholder('Enter legislator name')
                    ->required()
                    ->markAsRequired(false)
                    ->autocomplete(false)
                    ->validationAttribute('legislator'),

                Select::make("particular")
                    ->relationship("particular", "name")
                    ->required()
                    ->markAsRequired(false)
                    ->searchable()
                    ->preload()
                    ->multiple()
                    ->native(false)
                    ->options(fn() => self::getParticularOptions())
                    ->disableOptionWhen(fn($value) => $value === 'no_particular')
                    ->validationAttribute('particular'),

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
            ->defaultSort('name')
            ->emptyStateHeading('No legislators available')
            ->columns([
                TextColumn::make("name")
                    ->label('Legislator')
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
                    ->label('Records')
                    ->visible(fn() => Auth::user()->hasRole(['Super Admin', 'Admin']) || Auth::user()->can('filter legislator')),

                Filter::make('fundSource')
                    ->form([
                        Select::make('status_id')
                            ->label("Status")
                            ->placeholder('All')
                            ->relationship('status', 'desc')
                            ->reactive(),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['status_id'] ?? null,
                                fn(Builder $query, $statusId) => $query->where('status_id', $statusId)
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if (!empty($data['status_id'])) {
                            $indicators[] = 'Status: ' . Optional(Status::find($data['status_id']))->desc;
                        }

                        return $indicators;
                    })
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
                        })
                        ->visible(fn() => Auth::user()->hasRole(['Super Admin', 'Admin']) || Auth::user()->can('delete legislator')),
                    RestoreBulkAction::make()
                        ->action(function ($records) {
                            $records->each->restore();

                            NotificationHandler::sendSuccessNotification('Restored', 'Selected legislators have been restored successfully.');
                        })
                        ->visible(fn() => Auth::user()->hasRole('Super Admin') || Auth::user()->can('restore legislator')),
                    ForceDeleteBulkAction::make()
                        ->action(function ($records) {
                            $records->each->forceDelete();

                            NotificationHandler::sendSuccessNotification('Force Deleted', 'Selected legislators have been deleted permanently.');
                        })
                        ->visible(fn() => Auth::user()->hasRole('Super Admin') || Auth::user()->can('force delete legislator')),
                    ExportBulkAction::make()
                        ->exports([
                            CustomLegislatorExport::make()
                                ->withColumns([
                                    Column::make('name')
                                        ->heading('Legislator'),
                                    Column::make('particular_name')
                                        ->heading('Particular')
                                        ->getStateUsing(function ($record) {
                                            if (!$record->particular) {
                                                return 'No particulars available';
                                            }

                                            return $record->particular->map(function ($particular) {
                                                $district = $particular->district;
                                                $municipality = $district ? $district->municipality()->first() : null;
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
                                        }),
                                    Column::make('status.desc')
                                        ->heading('Status'),
                                ])
                                ->withFilename(date('m-d-Y') . ' - Legislators'),
                        ])
                ]),
            ]);
    }

    protected static function getParticularOptions(): array
    {
        return Particular::query()
            ->with('district')
            ->get()
            ->mapWithKeys(fn($item) => self::formatParticular($item))
            ->toArray() ?: ['no_particular' => 'No particulars available'];
    }

    protected static function formatParticular($item): array
    {
        $subParticular = $item->subParticular->name ?? '-';

        if (in_array($subParticular, ['Senator', 'House Speaker', 'House Speaker (LAKAS)'])) {
            $formattedName = $subParticular;
        } elseif ($subParticular === 'Party-list') {
            $partylistName = $item->partylist->name ?? '-';
            $formattedName = "{$subParticular} - {$partylistName}";
        } elseif ($subParticular === 'District') {
            $districtName = $item->district->name ?? '-';
            $municipality = $item->district->underMunicipality->name ?? '';
            $provinceName = $item->district->province->name ?? '-';

            if ($municipality) {
                $formattedName = "{$subParticular} - {$districtName}, {$municipality}, {$provinceName}";
            } else {
                $formattedName = "{$subParticular} - {$districtName}, {$provinceName}";
            }
        } elseif ($subParticular === 'RO Regular' || $subParticular === 'CO Regular') {
            $districtName = $item->district->name ?? '-';
            $provinceName = $item->district->province->name ?? '-';
            $regionName = $item->district->province->region->name ?? '-';
            $formattedName = "{$subParticular} - {$regionName}";
        } else {
            $regionName = $item->district->province->region->name ?? '-';
            $formattedName = "{$subParticular} - {$regionName}";
        }

        return [$item->id => $formattedName];
    }

    protected static function getParticularNames($record): string
    {
        return $record->particular->map(function ($particular, $index) use ($record) {
            $districtName = $particular->district->name ?? '-';
            $provinceName = $particular->district->province->name ?? '-';
            $regionName = $particular->district->province->region->name ?? '-';

            $municipalityName = $particular->district->underMunicipality->name ?? null;

            $paddingTop = ($index > 0) ? 'padding-top: 15px;' : '';
            $comma = ($index < $record->particular->count() - 1) ? ',' : '';

            if ($particular->subParticular->name === 'Party-list') {
                $partylistName = $particular->partylist->name ?? '-';
                return '<div style="' . $paddingTop . '">' . $particular->subParticular->name . ' - ' . $partylistName . $comma . '</div>';
            } elseif (in_array($particular->subParticular->name, ['Senator', 'House Speaker', 'House Speaker (LAKAS)'])) {
                return '<div style="' . $paddingTop . '">' . $particular->subParticular->name . $comma . '</div>';
            } elseif ($particular->subParticular->name === 'District') {
                if ($municipalityName) {
                    $municipalityFormatted = "{$districtName}, {$municipalityName}, {$provinceName}";
                } else {
                    $municipalityFormatted = "{$districtName}, {$provinceName}, {$regionName}";
                }
                return '<div style="' . $paddingTop . '">' . $particular->subParticular->name . ' - ' . $municipalityFormatted . $comma . '</div>';
            } elseif ($particular->subParticular->name === 'RO Regular' || $particular->subParticular->name === 'CO Regular') {
                return '<div style="' . $paddingTop . '">' . $particular->subParticular->name . ' - ' . $regionName . $comma . '</div>';
            } else {
                return '<div style="' . $paddingTop . '">' . $particular->subParticular->name . ' - ' . $regionName . $comma . '</div>';
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
