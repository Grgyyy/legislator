<?php

namespace App\Providers\Filament;

use App\Models\Legislator;
use Devonab\FilamentEasyFooter\EasyFooterPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Joaopaulolndev\FilamentEditProfile\FilamentEditProfilePlugin;
use Joaopaulolndev\FilamentEditProfile\Pages\EditProfilePage;
use Leandrocfe\FilamentApexCharts\FilamentApexChartsPlugin;


class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel

            ->default()
            ->id('admin')
            // ->path('')
            ->login()
            ->colors([
                'danger' => Color::Rose,
                'gray' => Color::Gray,
                'info' => Color::Blue,
                'success' => Color::Emerald,
                'warning' => Color::Orange,
                'primary' => '#78a2cc',
            ])
            ->font('Poppins')
            // ->brandLogo('images/TESDA_logo.png')
            ->brandName('Legislator App')

            //             ->brandLogo(fn() => new HtmlString('
//     <div style="display: flex; align-items: center; gap: 2px; margin: 0; padding: 0;">
//         <img src="' . asset('images/TESDA_logo.png') . '" alt="Logo" style="height: 45px; margin: 0; padding: 0;">
//         <span style="font-size: 14px; font-weight: bold; margin: 0; padding: 0;">
//             Legislative Information System
//         </span>
//     </div>
// '))
            ->favicon('images/TESDA_logo.png')
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                // Widgets\AccountWidget::class,
                // Widgets\FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->sidebarFullyCollapsibleOnDesktop()
            ->plugins([
                FilamentEditProfilePlugin::make()
                    ->slug('my-profile')
                    ->setTitle('User Profile')
                    ->setNavigationLabel('User Profile')
                    ->setNavigationGroup('USER MANAGEMENT')
                    ->setIcon('heroicon-o-user')
                    ->setSort(1)
                    ->shouldShowDeleteAccountForm(false)
                    ->shouldShowBrowserSessionsForm(true)
                    ->shouldShowAvatarForm(
                        value: false,
                        directory: 'public/images/avatars',
                        rules: 'mimes:jpeg,png|max:1024',
                    ),
                FilamentApexChartsPlugin::make(),
                EasyFooterPlugin::make()
                    ->footerEnabled()
                    ->withFooterPosition('footer')
                    ->withSentence('Legislative Information System')
                    ->withLogo(
                        'https://www.tesda.gov.ph/Content/images/logos/TESDA%20Logo%20official.png',
                        'https://laravel.com',
                        'TESDA - Regional Operations Management Office ',
                        18
                    )
                    ->withLinks([
                        [
                            'title' => 'Manual',
                            'url' => 'https://docs.google.com/document/d/1-TlUIOWk7I-SLsWEwLGDwD-kYndNrrJH8wPGv9G5X5c/edit?usp=sharing', 
                        ],
                    ])
            ])
            ->userMenuItems([
                'profile' => MenuItem::make()
                    ->label(fn() => auth()->user()->name)
                    ->url(fn(): string => EditProfilePage::getUrl())
                    ->icon('heroicon-m-user-circle')
            ])
            ->navigationGroups([
                'TARGET DATA INPUT',
                'SECTORS',
                'MANAGE TARGET',
                'USER MANAGEMENT',
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\\Filament\\Clusters');
    }
}
