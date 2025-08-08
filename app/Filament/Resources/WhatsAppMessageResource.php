<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WhatsAppMessageResource\Pages;
use App\Filament\Resources\WhatsAppMessageResource\RelationManagers;
use App\Models\WhatsAppMessage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class WhatsAppMessageResource extends Resource
{
    protected static ?string $model = WhatsAppMessage::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('abandoned_cart_id')
                    ->relationship('abandonedCart', 'shopify_cart_id')
                    ->required(),
                Forms\Components\TextInput::make('message_id')->required(),
                Forms\Components\TextInput::make('status')->required(),
                Forms\Components\DateTimePicker::make('sent_at'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('abandonedCart.shopify_cart_id')->sortable(),
                Tables\Columns\TextColumn::make('message_id')->sortable(),
                Tables\Columns\TextColumn::make('status')->sortable(),
                Tables\Columns\TextColumn::make('sent_at')->dateTime(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'sent' => 'Enviado',
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
            'index' => Pages\ListWhatsAppMessages::route('/'),
            'create' => Pages\CreateWhatsAppMessage::route('/create'),
            'edit' => Pages\EditWhatsAppMessage::route('/{record}/edit'),
        ];
    }
}
