<?php

namespace IainConnor\LumenSwaggerUi;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Laravel\Lumen\Routing\Controller;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class LumenSwaggerUiController extends Controller
{
    const ONE_WEEK = 60 * 60 * 24 * 7;
    /** @var Filesystem */
    protected $fileSystem;
    /** @var Request */
    protected $request;
    /** @var Repository */
    protected $config;
    /** @var string */
    protected $distDirectory;
    /** @var string[] */
    protected $resolvedAssets;

    /**
     * LumenSwaggerUiController constructor.
     *
     * @param Filesystem $fileSystem
     * @param Request    $request
     * @param Repository $config
     *
     * @internal param Application $app
     */
    public function __construct(
        Filesystem $fileSystem,
        Request $request,
        Repository $config
    ) {
        $this->fileSystem = $fileSystem;
        $this->request = $request;
        $this->config = $config;
    }

    /**
     * Renders the HTML for SwaggerUI.
     *
     * @return Response
     */
    public function getDocumentation()
    {
        return new Response($this->getSwaggerUiHtml());
    }

    /**
     * Returns the requested asset file, if it's allowed.
     *
     * @param string $asset
     * @param int    $cacheTime
     *
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws NotFoundHttpException
     */
    public function getAsset(string $asset, $cacheTime = LumenSwaggerUiController::ONE_WEEK)
    {
        if (!$this->isAssetAllowedForDisplay($asset)) {
            throw new NotFoundHttpException();
        }

        $actualAssetFile = $this->getSwaggerUiDistDirectory() . DIRECTORY_SEPARATOR . $asset;

        return \response()->download($actualAssetFile,
                                     null,
                                     ['Content-Type' => $this->getMimeType($actualAssetFile)],
                                     ResponseHeaderBag::DISPOSITION_INLINE)
                          ->setSharedMaxAge($cacheTime)
                          ->setMaxAge($cacheTime)
                          ->setExpires(new \DateTime('@' . (time() + $cacheTime)));
    }

    /**
     * A "fix" for https://bugs.php.net/bug.php?id=53035.
     *
     * @param string $file
     * @param bool   $trustExtensionWhenUnsure
     *
     * @return false|string
     */
    private function getMimeType(string $file, bool $trustExtensionWhenUnsure = true)
    {
        $mimeType = $this->fileSystem->mimeType($file);

        if ($mimeType == 'text/plain' && $trustExtensionWhenUnsure) {
            switch (pathinfo($file, PATHINFO_EXTENSION)) {
                case 'css':
                    return 'text/css';
                    break;
                case 'js':
                    return 'application/javascript';
                case 'json':
                    return 'application/json';
                default:
                    break;
            }
        }

        return $mimeType;
    }

    /**
     * Downloads the JSON spec file.
     *
     * @param string|null $file
     * @param int         $cacheTime
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function getDownload(string $file = null, $cacheTime = LumenSwaggerUiController::ONE_WEEK)
    {
        $responseFileName = $this->config->get(LumenSwaggerUiServiceProvider::CONFIG_NAME .
                                               '.download-filename');
        $actualFileName = $this->config->get(LumenSwaggerUiServiceProvider::CONFIG_NAME .
                                             '.swagger-json-file');

        if ($file === null || $file === $responseFileName) {

            return \response()->download($actualFileName,
                                         $responseFileName,
                                         ['Content-Type' => $this->getMimeType($actualFileName)],
                                         $file ===
                                         null ? ResponseHeaderBag::DISPOSITION_ATTACHMENT : ResponseHeaderBag::DISPOSITION_INLINE)
                              ->setSharedMaxAge($cacheTime)
                              ->setMaxAge($cacheTime)
                              ->setExpires(new \DateTime('@' . (time() + $cacheTime)));
        }

        throw new NotFoundHttpException();
    }

    /**
     * Gets the HTML for the OAuth2 Callback.
     *
     * @return Response
     */
    public function getOAuth2Callback()
    {
        return new Response($this->fileSystem->get($this->getSwaggerUiDistDirectory() .
                                                   DIRECTORY_SEPARATOR .
                                                   'oauth2-redirect.html'));
    }

    /**
     * Gets the path to the vendor directory.
     *
     * @return string
     * @throws VendorNotFoundException
     */
    protected function getVendorDir()
    {
        $vendorDir = realpath(__DIR__ . "/../../vendor") ?: realpath(__DIR__ . "/../../../../../vendor");

        if (!is_dir($vendorDir)) {
            throw new VendorNotFoundException();
        }

        return $vendorDir;
    }

    /**
     * Gets the SwaggerUI dist component directory for CSS and JS files.
     *
     * @return string
     * @throws VendorNotFoundException
     */
    protected function getSwaggerUiDistDirectory()
    {
        if ($this->distDirectory == null) {
            $this->distDirectory = $this->getVendorDir() . "/swagger-api/swagger-ui/dist";

            if (!is_dir($this->distDirectory)) {
                throw new VendorNotFoundException();
            }
        }

        return $this->distDirectory;
    }

    /**
     * Checks if the provided asset name is allowed for display.
     *
     * @param $asset
     *
     * @return bool
     */
    protected function isAssetAllowedForDisplay($asset)
    {
        // @TODO, this would be better with a longer lived cache.
        if (!isset($this->resolvedAssets[$asset])) {
            $allowedExtensions = ['css', 'js', 'png'];

            $swaggerUiDistDirectory = $this->getSwaggerUiDistDirectory();

            // Only get allowed files.
            $allowedFiles = array_filter($this->fileSystem->files($swaggerUiDistDirectory),
                function ($element) use ($allowedExtensions) {
                    return array_search($this->fileSystem->extension($element), $allowedExtensions) !== false;
                });

            // Only get file names of those files.
            $allowedFiles = array_map(function ($element) {
                return $this->fileSystem->name($element) . '.' . $this->fileSystem->extension($element);
            }, $allowedFiles);

            $this->resolvedAssets[$asset] = array_search($asset, $allowedFiles) !== false;
        }

        return $this->resolvedAssets[$asset];
    }

    /**
     * Returns the SwaggerUI HTML with swaps for our directory structure.
     *
     * @return string
     */
    protected function getSwaggerUiHtml()
    {
        $assetRoute = $this->config->get(LumenSwaggerUiServiceProvider::CONFIG_NAME . '.routes.assets');

        $searchReplacements = [
            'src="./' => 'src="' . $assetRoute . '/',
            'href="./' => 'href="' . $assetRoute . '/',
            'http://petstore.swagger.io/v2/swagger.json' => $this->request->getSchemeAndHttpHost() .
                                                            $this->config->get(LumenSwaggerUiServiceProvider::CONFIG_NAME .
                                                                               '.routes.download') .
                                                            '/' .
                                                            $this->config->get(LumenSwaggerUiServiceProvider::CONFIG_NAME .
                                                                               '.download-filename'),
            'Swagger UI' => $this->config->get(LumenSwaggerUiServiceProvider::CONFIG_NAME . '.title'),
        ];

        return str_replace(array_keys($searchReplacements), array_values($searchReplacements),
                           $this->fileSystem->get($this->getSwaggerUiDistDirectory() .
                                                  DIRECTORY_SEPARATOR .
                                                  "index.html"));
    }
}
