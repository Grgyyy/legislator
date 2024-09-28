<?php

namespace App\Filament\Resources;

use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\RestoreAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use App\Models\Particular;
use Filament\Pages\Page;
use Filament\Resources\Pages\CreateRecord;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use App\Filament\Resources\LegislatorResource\Pages;
use App\Models\Legislator;
use App\Models\Status;
use Filament\Forms\Form;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
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
                    ->required()
                    ->autocomplete(false)
                    ->markAsRequired(false),
                Select::make("particular")
                    ->multiple()
                    ->relationship("particular", "name")
                    ->options(fn() => self::getParticularOptions())
                    ->required()
                    ->markAsRequired(false)
                    ->native(false)
                    ->preload()
                    ->disableOptionWhen(fn($value) => $value === 'no_particular'),
                Select::make('status_id')
                    ->label('Status')
                    ->default(1)
                    ->relationship('status', 'desc')
                    ->hidden(fn(Page $livewire) => $livewire instanceof CreateRecord)
                    ->required()
                    ->markAsRequired(false)
                    ->native(false)
                    ->options(fn() => self::getStatusOptions())
                    ->disableOptionWhen(fn($value) => $value === 'no_status'),
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
        } elseif ($subParticular === 'Partylist') {
            $formattedName = "{$item->subParticular->name} - {$item->partylist->name}";
        } else {
            $formattedName = "{$item->subParticular->name} - {$item->district->name}, {$item->district->municipality->name}";
        }

        return [$item->id => $formattedName];
    }

    protected static function getStatusOptions(): array
    {
        return Status::all()->pluck('desc', 'id')->toArray() ?: ['no_status' => 'No Status Available'];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading('No legislators yet')
            ->columns([
                TextColumn::make("name")
                    ->label('Name')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('particular_name')
                    ->label('Particular')
                    ->getStateUsing(fn($record) => self::getParticularNames($record))
                    ->html()
                    ->toggleable(),
                TextColumn::make("status.desc")
                    ->toggleable(),
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
                    DeleteAction::make(),
                    RestoreAction::make(),
                    ForceDeleteAction::make(),
                ])
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
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
                                                } elseif ($subParticular === 'Partylist') {
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

    protected static function getParticularNames($record): string
    {
        return $record->particular->map(function ($particular, $index) {
            $municipalityName = $particular->district->name . ', ' . $particular->district->municipality->name;

            $paddingTop = ($index > 0) ? 'padding-top: 15px;' : '';

            if ($particular->subParticular->name === 'Partylist') {
                return '<div style="' . $paddingTop . '">' . $particular->subParticular->name . ' - ' . $particular->partylist->name . '</div>';
            } elseif (in_array($particular->subParticular->name, ['Senator', 'House Speaker', 'House Speaker (LAKAS)'])) {
                return '<div style="' . $paddingTop . '">' . $particular->subParticular->name . '</div>';
            } else {
                return '<div style="' . $paddingTop . '">' . $particular->subParticular->name . ' - ' . $municipalityName . '</div>';
            }
        })->implode('');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLegislators::route('/'),
            'create' => Pages\CreateLegislator::route('/create'),
            'edit' => Pages\EditLegislator::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->whereNotIn('name', ['Regional Office', 'Central Office']);
    }


    // protected static function isTrashedFilterActive(): bool
    // {
    //     $filters = request()->query('tableFilters', []);
    //     return isset($filters['status']['status_id']) && $filters['status']['status_id'] === 'deleted';
    // }
}
