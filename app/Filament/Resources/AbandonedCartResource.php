<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AbandonedCartResource\Pages;
use App\Filament\Resources\AbandonedCartResource\RelationManagers;
use App\Models\AbandonedCart;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AbandonedCartResource extends Resource
{
    protected static ?string $model = AbandonedCart::class;

    protected static ?string $tenantOwnershipRelationshipName = 'tenant';

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('shopify_cart_id')->required(),
                Forms\Components\TextInput::make('customer_email')->email()->required(),
                Forms\Components\TextInput::make('total_price')->numeric()->required(),
                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => 'Pendiente',
                        'recovered' => 'Recuperado',
                        'failed' => 'Fallido',
                    ])->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('shopify_cart_id')->sortable(),
                Tables\Columns\TextColumn::make('customer_email')->sortable(),
                Tables\Columns\TextColumn::make('total_price')->money('usd'),
                Tables\Columns\TextColumn::make('status')->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pendiente',
                        'recovered' => 'Recuperado',
                        'failed' => 'Fallido',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAbandonedCarts::route('/'),
            'create' => Pages\CreateAbandonedCart::route('/create'),
            'edit' => Pages\EditAbandonedCart::route('/{record}/edit'),
        ];
    }
}
