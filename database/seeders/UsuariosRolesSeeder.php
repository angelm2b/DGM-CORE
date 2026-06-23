<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Permiso;
use App\Models\Rol;
use App\Models\Usuario;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UsuariosRolesSeeder extends Seeder
{
    public function run(): void
    {
        // --- Permisos ---
        $permisos = [
            'personas.crear' => 'Crear personas',
            'personas.ver' => 'Consultar personas',
            'solicitudes.crear' => 'Crear solicitudes',
            'solicitudes.ver' => 'Consultar solicitudes',
            'solicitudes.transicionar' => 'Transicionar solicitudes',
            'adjuntos.cargar' => 'Cargar adjuntos',
            'ordenes.ver' => 'Consultar órdenes de pago',
            'pagos.registrar' => 'Registrar pagos',
            'movimientos.registrar' => 'Registrar movimientos migratorios',
            'movimientos.ver' => 'Consultar movimientos migratorios',
            'documentos.emitir' => 'Emitir documentos',
            'documentos.verificar' => 'Verificar documentos',
            'calculos.ver' => 'Consultar cálculos',
            'catalogos.ver' => 'Consultar catálogos',
        ];

        foreach ($permisos as $codigo => $nombre) {
            Permiso::updateOrCreate(['codigo' => $codigo], ['nombre' => $nombre]);
        }

        $todos = Permiso::pluck('id', 'codigo');
        $soloLectura = $todos->filter(fn ($id, $cod) => str_ends_with($cod, '.ver') || str_ends_with($cod, '.verificar'));

        // --- Roles ---
        $roles = [
            'ADMIN_DGM' => ['nombre' => 'Administrador DGM', 'permisos' => $todos->keys()],
            'ANALISTA_EXTRANJERIA' => ['nombre' => 'Analista de Extranjería', 'permisos' => collect([
                'personas.crear', 'personas.ver', 'solicitudes.crear', 'solicitudes.ver',
                'solicitudes.transicionar', 'adjuntos.cargar', 'ordenes.ver',
                'documentos.emitir', 'documentos.verificar', 'calculos.ver', 'catalogos.ver',
            ])],
            'AUDITOR' => ['nombre' => 'Auditor', 'permisos' => $soloLectura->keys()],
            'INTEGRADOR' => ['nombre' => 'Sistema de Integración', 'permisos' => $todos->keys()],
        ];

        foreach ($roles as $codigo => $cfg) {
            $rol = Rol::updateOrCreate(['codigo' => $codigo], ['nombre' => $cfg['nombre']]);
            $ids = collect($cfg['permisos'])->map(fn ($c) => $todos[$c] ?? null)->filter()->all();
            $rol->permisos()->sync($ids);
        }

        // --- Usuarios (uno por rol) ---
        $usuarios = [
            ['nombre' => 'Administrador DGM', 'email' => 'admin@dgm.gob.do', 'rol' => 'ADMIN_DGM'],
            ['nombre' => 'Analista Extranjería', 'email' => 'analista@dgm.gob.do', 'rol' => 'ANALISTA_EXTRANJERIA'],
            ['nombre' => 'Auditor DGM', 'email' => 'auditor@dgm.gob.do', 'rol' => 'AUDITOR'],
        ];

        // Contraseña por defecto solo para entornos locales/demo.
        // Definir SEED_DEFAULT_PASSWORD en .env; si no, se genera una aleatoria.
        $defaultPassword = env('SEED_DEFAULT_PASSWORD') ?: Str::random(32);

        $rolesById = Rol::pluck('id', 'codigo');
        foreach ($usuarios as $u) {
            Usuario::updateOrCreate(
                ['email' => $u['email']],
                [
                    'nombre' => $u['nombre'],
                    'password' => Hash::make($defaultPassword),
                    'rol_id' => $rolesById[$u['rol']],
                    'activo' => true,
                ],
            );
        }

        // --- Cliente "integrador" con token Sanctum de larga vida ---
        $integrador = Usuario::updateOrCreate(
            ['email' => 'integrador@dgm.gob.do'],
            [
                'nombre' => 'Sistema de Integración',
                'password' => Hash::make(Str::random(32)),
                'rol_id' => $rolesById['INTEGRADOR'],
                'activo' => true,
            ],
        );

        // Crea el token solo si aún no existe (idempotencia) y lo expone una vez.
        if ($integrador->tokens()->where('name', 'integrador')->doesntExist()) {
            $token = $integrador->createToken('integrador', ['*'])->plainTextToken;
            $rutaToken = storage_path('integrador_token.txt');
            file_put_contents($rutaToken, $token.PHP_EOL);
            $this->command?->warn('==================================================================');
            $this->command?->warn('TOKEN SANCTUM DEL INTEGRADOR (guardado en storage/integrador_token.txt):');
            $this->command?->warn($token);
            $this->command?->warn('==================================================================');
        } else {
            $this->command?->info('El token del integrador ya existe; no se regeneró.');
        }
    }
}
