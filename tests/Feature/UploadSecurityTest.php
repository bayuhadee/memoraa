<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Photo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UploadSecurityTest extends TestCase
{
    use RefreshDatabase;

    private Event $event;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $this->event = Event::factory()->create();
        $this->token = $this->newToken();
        $this->guestFor($this->event, $this->token);
    }

    private function upload(UploadedFile $file)
    {
        return $this->asDevice($this->token)->post(route('guest.photos.store', $this->event), ['photos' => [$file]]);
    }

    private function assertRejected(UploadedFile $file): void
    {
        $this->upload($file)->assertSessionHasErrors('photos.0');
        $this->assertSame(0, Photo::count());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_rejects_text_file_renamed_to_jpg(): void
    {
        $this->assertRejected(UploadedFile::fake()->createWithContent('foto.jpg', 'ini bukan gambar'));
    }

    public function test_rejects_php_file_disguised_as_image(): void
    {
        $this->assertRejected(UploadedFile::fake()->createWithContent('shell.php.jpg', '<?php system($_GET["c"]); ?>'));
    }

    public function test_rejects_svg(): void
    {
        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="500" height="500"><script>alert(1)</script></svg>';
        $this->assertRejected(UploadedFile::fake()->createWithContent('gambar.svg', $svg));
    }

    public function test_rejects_valid_jpeg_with_embedded_php_polyglot(): void
    {
        $file = $this->jpeg(800, 600, 'polyglot.jpg');
        file_put_contents($file->getRealPath(), file_get_contents($file->getRealPath()).'<?php echo shell_exec($_GET["x"]); ?>');

        $this->assertRejected($file);
    }

    public function test_rejects_too_small_image(): void
    {
        $this->assertRejected($this->jpeg(100, 100, 'kecil.jpg'));
    }

    public function test_rejects_animated_gif(): void
    {
        $this->assertRejected(UploadedFile::fake()->createWithContent('anim.gif', $this->animatedGif()));
    }

    public function test_rejects_decompression_bomb_by_pixel_count(): void
    {
        config(['galeri.photos.max_megapixels' => 1]); // 1 MP agar test cepat
        $this->assertRejected($this->jpeg(1500, 1000, 'besar.jpg'));
    }

    public function test_accepts_png_and_reencodes_to_jpeg(): void
    {
        $this->upload(UploadedFile::fake()->image('foto.png', 1000, 800))->assertSessionHasNoErrors();

        $photo = Photo::sole();
        $this->assertSame('image/jpeg', $photo->mime);
        $this->assertStringEndsWith('.jpg', $photo->path);
        $this->assertSame('image/jpeg', mime_content_type(Storage::disk('local')->path($photo->path)));
    }

    public function test_stored_filename_is_random_and_not_from_user(): void
    {
        $this->upload($this->jpeg(800, 600, '../../evil name.php.jpg'))->assertSessionHasNoErrors();

        $photo = Photo::sole();
        $this->assertMatchesRegularExpression('#^photos/\d+/[0-9a-f-]{36}\.jpg$#', $photo->path);
        $this->assertStringNotContainsString('/', $photo->original_name);
    }

    /** GIF 2 frame: duplikasi blok gambar dari GIF statis buatan GD. */
    private function animatedGif(): string
    {
        $im = imagecreatetruecolor(300, 300);
        ob_start();
        imagegif($im);
        $gif = ob_get_clean();

        $flags = ord($gif[10]);
        $offset = 13 + (($flags & 0x80) ? 3 * (2 ** (($flags & 0x07) + 1)) : 0);

        // Lewati extension block yang mungkin sudah ada.
        while ($gif[$offset] === "\x21") {
            $offset += 2;
            while (($len = ord($gif[$offset])) !== 0) {
                $offset += $len + 1;
            }
            $offset++;
        }

        $head = substr($gif, 0, $offset);
        $frame = substr($gif, $offset, -1); // tanpa trailer ';'
        $gce = "\x21\xF9\x04\x00\x0A\x00\x00\x00";

        return $head.$gce.$frame.$gce.$frame.';';
    }
}
