<?php

namespace App\Support;

use Barryvdh\DomPDF\Facade\Pdf as DomPdf;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\File;
use Mpdf\Config\ConfigVariables;
use Mpdf\Config\FontVariables;
use Mpdf\Mpdf;
use Mpdf\Output\Destination;
use Symfony\Component\HttpFoundation\Response;

/**
 * Makes the downloadable PDFs, in the language the person chose. English
 * PDFs use DomPDF as before; Tamil PDFs use mPDF, which joins Tamil letters
 * correctly (DomPDF cannot). Used like the DomPDF facade:
 * ReportPdf::loadView($view, $data)->setPaper('a4', 'portrait')->download($name).
 */
class ReportPdf
{
    /**
     * @param  array<string, mixed>  $data
     */
    private function __construct(
        private string $view,
        private array $data,
        private string $paper = 'a4',
        private string $orientation = 'portrait',
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function loadView(string $view, array $data = []): self
    {
        return new self($view, $data);
    }

    public function setPaper(string $paper, string $orientation = 'portrait'): self
    {
        $this->paper = $paper;
        $this->orientation = $orientation;

        return $this;
    }

    public static function isTamil(): bool
    {
        return App::getLocale() === 'ta';
    }

    public function download(string $fileName): Response
    {
        if (! self::isTamil()) {
            return DomPdf::loadView($this->view, $this->data)
                ->setPaper($this->paper, $this->orientation)
                ->download($fileName);
        }

        return response($this->tamilPdf(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
        ]);
    }

    /**
     * The same template rendered by mPDF, with Tamil text in a Tamil font.
     */
    private function tamilPdf(): string
    {
        $tempDir = storage_path('framework/cache/mpdf-tamil');
        File::ensureDirectoryExists($tempDir);

        $defaultConfig = (new ConfigVariables)->getDefaults();
        $defaultFonts = (new FontVariables)->getDefaults();

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => ucfirst($this->paper).($this->orientation === 'landscape' ? '-L' : ''),
            'tempDir' => $tempDir,
            'default_font' => 'dejavusans',
            /* Tamil text is marked as Tamil and set in Hind Madurai (regular + bold),
               in place of mPDF's FreeSerif, which has no bold Tamil letters */
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
            'fontDir' => array_merge($defaultConfig['fontDir'], [resource_path('fonts')]),
            'fontdata' => array_merge($defaultFonts['fontdata'], [
                'freeserif' => [
                    'R' => 'HindMadurai-Regular.ttf',
                    'B' => 'HindMadurai-Bold.ttf',
                    'useOTL' => 0xFF,
                ],
            ]),
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 10,
            'margin_bottom' => 10,
        ]);

        $mpdf->SetTitle(pathinfo((string) $this->view, PATHINFO_FILENAME));
        $mpdf->WriteHTML(view($this->view, $this->data)->render());

        return $mpdf->Output('', Destination::STRING_RETURN);
    }
}
