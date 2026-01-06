<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HelpController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $helpContent = [];

        // Common section for everyone
        $helpContent[] = [
            'title' => 'Connexion & Profil',
            'icon' => 'login',
            'color' => '#2ecc71',
            'steps' => [
                'Connectez-vous avec votre email et mot de passe.',
                'Accédez à votre profil pour mettre à jour vos informations personnelles et votre mot de passe.',
            ],
        ];

        if ($user->hasRole('Receptionist') || $user->hasRole('Admin') || $user->hasRole('Super Admin')) {
            $helpContent[] = [
                'title' => 'Gestion des Clients',
                'icon' => 'people',
                'color' => '#3498db',
                'steps' => [
                    'Consultez la liste des clients existants.',
                    'Ajoutez de nouveaux clients en remplissant le formulaire.',
                    'Recherchez un client spécifique par son nom ou son contact.',
                ],
            ];
            $helpContent[] = [
                'title' => 'Gestion des Réservations',
                'icon' => 'book',
                'color' => '#9b59b6',
                'steps' => [
                    'Créez une réservation pour un client en choisissant un hébergement ou une salle.',
                    'Effectuez les actions de Check-in, Check-out ou Annulation.',
                    'Gérez les paiements et imprimez les reçus.',
                ],
            ];
        }

        if ($user->hasRole('Admin') || $user->hasRole('Super Admin')) {
            $helpContent[] = [
                'title' => 'Gestion des Hébergements & Salles',
                'icon' => 'hotel',
                'color' => '#e67e22',
                'steps' => [
                    'Ajoutez, modifiez ou supprimez des hébergements et des salles de conférence.',
                    'Définissez leurs tarifs et leurs disponibilités.',
                ],
            ];
            $helpContent[] = [
                'title' => 'Gestion des Services',
                'icon' => 'room_service',
                'color' => '#f1c40f',
                'steps' => [
                    'Créez de nouveaux services additionnels (ex: Petit-déjeuner).',
                    'Fixez le prix pour chaque service.',
                ],
            ];
        }

        if ($user->hasRole('Super Admin')) {
            $helpContent[] = [
                'title' => 'Administration du Système',
                'icon' => 'settings',
                'color' => '#e74c3c',
                'steps' => [
                    'Créez et gérez les utilisateurs.',
                    'Définissez les rôles et assignez-leur des privilèges spécifiques.',
                    'Supervisez l\'ensemble des opérations de l\'hôtel.',
                ],
            ];
        }

        return response()->json($helpContent);
    }
}
