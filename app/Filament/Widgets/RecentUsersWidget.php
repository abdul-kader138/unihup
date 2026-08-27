<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentUsersWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 1;

    public function table(Table $table): Table
    {
        return $table
            ->heading('Recent users')
            ->query(User::query()->with('roles')->latest())
            ->columns([
                TextColumn::make('name')
                    ->label('Name'),
                TextColumn::make('email')
                    ->label('Email')
                    ->limit(24),
                TextColumn::make('email_verified_at')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state ? 'Verified' : 'Pending')
                    ->color(fn ($state): string => $state ? 'success' : 'warning'),
            ])
            ->paginated(false)
            ->defaultPaginationPageOption(5);
    }
}
