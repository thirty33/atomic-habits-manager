<?php

namespace Database\Seeders;

use App\Enums\ConversationStatus;
use App\Enums\MessageRole;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ConversationSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->first();

        $conversation = Conversation::create([
            'user_id' => $user->user_id,
            'title' => 'Mi primer chat con Atomic IA',
            'status' => ConversationStatus::Active,
            'last_message_at' => Carbon::now(),
        ]);

        $messages = [
            ['role' => MessageRole::Assistant, 'body' => '¡Hola! Soy Atomic IA, tu asistente de hábitos atómicos. Estoy aquí para ayudarte a construir mejores hábitos y dejar los que no te sirven. ¿En qué puedo ayudarte hoy?', 'minutes' => 0],
            ['role' => MessageRole::User, 'body' => '¿Cuáles son mis hábitos activos?', 'minutes' => 1],
            ['role' => MessageRole::Assistant, 'body' => "Tienes 5 hábitos activos:\n\n1. **Hacer ejercicio** — Lun, Mié, Vie de 06:30 a 07:30\n2. **Meditar** — Cada 3 días de 07:00 a 07:20\n3. **Tomar agua** — Lun a Vie de 09:00 a 09:15\n4. **Estudiar todos los días** — Sin programación definida\n5. **Dejar de fumar** — Cada 5 días de 10:00 a 10:30\n\n¿Te gustaría ver el detalle de alguno?", 'minutes' => 1],
            ['role' => MessageRole::User, 'body' => 'Sí, cuéntame sobre Meditar', 'minutes' => 2],
            ['role' => MessageRole::Assistant, 'body' => "Tu hábito **Meditar** está configurado así:\n\n- **Naturaleza:** Quiero adoptar un buen hábito\n- **Frecuencia:** Cada 3 días\n- **Horario:** 07:00 – 07:20\n- **Activo desde:** 5 de febrero de 2026\n\n¡Vas muy bien! La meditación es uno de los hábitos más poderosos según James Clear. ¿Quieres que te dé consejos para mantener la racha?", 'minutes' => 2],
            ['role' => MessageRole::User, 'body' => '¿Qué consejo me das para no romper la racha?', 'minutes' => 4],
            ['role' => MessageRole::Assistant, 'body' => "Aquí van 3 consejos de Atomic Habits para mantener tu racha:\n\n1. **Nunca faltes dos veces seguidas** — Si un día no meditas, el siguiente es obligatorio. Un mal día no rompe el hábito, pero dos sí.\n\n2. **Reduce la fricción** — Deja tu cojín de meditación listo la noche anterior. Que sea más fácil hacerlo que no hacerlo.\n\n3. **Vincula el hábito** — Usa el \"habit stacking\": después de levantarte y antes de desayunar, medita. Así se convierte en parte de tu rutina automática.\n\n¿Quieres que te ayude a configurar una señal (cue) para este hábito?", 'minutes' => 4],
            ['role' => MessageRole::User, 'body' => 'Sí, configúralo con "Después de lavarme la cara"', 'minutes' => 6],
            ['role' => MessageRole::Assistant, 'body' => '¡Perfecto! He registrado la señal **"Después de lavarme la cara"** para tu hábito de Meditar. Ahora tu intención de implementación queda así: "Después de lavarme la cara, meditaré 20 minutos en mi habitación". ¿Hay algo más en lo que pueda ayudarte?', 'minutes' => 6],
        ];

        $baseTime = Carbon::now()->subMinutes(10);

        foreach ($messages as $msg) {
            Message::create([
                'conversation_id' => $conversation->conversation_id,
                'role' => $msg['role'],
                'body' => $msg['body'],
                'created_at' => $baseTime->copy()->addMinutes($msg['minutes']),
                'updated_at' => $baseTime->copy()->addMinutes($msg['minutes']),
            ]);
        }
    }
}
