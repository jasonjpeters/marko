type PageModule = unknown;
type PageLoader = () => Promise<unknown>;
export type MarkoInertiaPageEntry = PageLoader | PageModule;
export type MarkoInertiaPages = Record<string, MarkoInertiaPageEntry>;

type ResolvedPage = {
  key: string;
  componentPath: string;
  isRoot: boolean;
  moduleFullName: string | null;
  moduleShortName: string | null;
};

export function createMarkoTitleResolver(
  titleResolver?: (title: string, appName: string) => string,
  appName: string = document.title || "Marko",
): (title?: string) => string {
  return (title?: string) => {
    const normalizedTitle = title ?? "";

    if (titleResolver) {
      return titleResolver(normalizedTitle, appName);
    }

    return normalizedTitle ? `${normalizedTitle} - ${appName}` : appName;
  };
}

export async function resolveMarkoPageComponent(
  component: string,
  pages: MarkoInertiaPages,
): Promise<unknown> {
  const match = findMarkoPage(component, pages);

  if (match === null) {
    throw new Error(
      `Unable to resolve Inertia page component "${component}". ` +
        "Check resources/js/pages or module resources/js/pages paths.",
    );
  }

  const page = pages[match];

  if (typeof page === "function") {
    return await page();
  }

  return page;
}

export function createMarkoPageResolver(
  pages: MarkoInertiaPages,
): (component: string) => Promise<unknown> {
  return (component) => resolveMarkoPageComponent(component, pages);
}

function findMarkoPage(
  component: string,
  pages: MarkoInertiaPages,
): string | null {
  const resolvedPages = Object.keys(pages)
    .map((key) => parseResolvedPage(key))
    .filter((page): page is ResolvedPage => page !== null);

  const parsedComponent = parseComponent(component);

  if (
    parsedComponent.moduleName === "" ||
    isRootAlias(parsedComponent.moduleName)
  ) {
    const rootPage = resolvedPages.find(
      (page) =>
        page.isRoot && page.componentPath === parsedComponent.componentPath,
    );

    if (rootPage) {
      return rootPage.key;
    }

    const firstModulePage = resolvedPages.find(
      (page) => page.componentPath === parsedComponent.componentPath,
    );

    return firstModulePage?.key ?? null;
  }

  const modulePage = resolvedPages.find(
    (page) =>
      page.componentPath === parsedComponent.componentPath &&
      (page.moduleShortName === parsedComponent.moduleName ||
        page.moduleFullName === parsedComponent.moduleName),
  );

  return modulePage?.key ?? null;
}

function parseComponent(component: string): {
  moduleName: string;
  componentPath: string;
} {
  const normalized = component.replaceAll("\\", "/").replace(/^\/+|\/+$/g, "");

  if (normalized.includes("::")) {
    const [moduleName, componentPath] = normalized.split("::", 2);

    return {
      moduleName,
      componentPath: componentPath.replace(/^\/+|\/+$/g, ""),
    };
  }

  return {
    moduleName: "",
    componentPath: normalized,
  };
}

function parseResolvedPage(key: string): ResolvedPage | null {
  const normalized = key.replaceAll("\\", "/");

  if (normalized.startsWith("./pages/") || normalized.startsWith("./Pages/")) {
    const prefix = normalized.startsWith("./pages/") ? "./pages/" : "./Pages/";

    return {
      key,
      componentPath: stripExtension(normalized.slice(prefix.length)),
      isRoot: true,
      moduleFullName: null,
      moduleShortName: null,
    };
  }

  const marker = ["/resources/js/pages/", "/resources/js/Pages/"].find(
    (candidate) => normalized.includes(candidate),
  );

  if (marker === undefined) {
    return null;
  }

  const markerIndex = normalized.indexOf(marker);

  const moduleRoot = normalized.slice(0, markerIndex);
  const componentPath = stripExtension(
    normalized.slice(markerIndex + marker.length),
  );

  const vendorMatch = moduleRoot.match(/\/vendor\/([^/]+)\/([^/]+)$/);

  if (vendorMatch) {
    return {
      key,
      componentPath,
      isRoot: false,
      moduleFullName: `${vendorMatch[1]}/${vendorMatch[2]}`,
      moduleShortName: vendorMatch[2],
    };
  }

  const appMatch = moduleRoot.match(/\/app\/([^/]+)$/);

  if (appMatch) {
    return {
      key,
      componentPath,
      isRoot: false,
      moduleFullName: `app/${appMatch[1]}`,
      moduleShortName: appMatch[1],
    };
  }

  const modulesMatch = moduleRoot.match(/\/modules\/([^/]+)$/);

  if (modulesMatch) {
    return {
      key,
      componentPath,
      isRoot: false,
      moduleFullName: modulesMatch[1],
      moduleShortName: modulesMatch[1],
    };
  }

  return {
    key,
    componentPath,
    isRoot: false,
    moduleFullName: null,
    moduleShortName: null,
  };
}

function stripExtension(path: string): string {
  return path.replace(/\.[^.]+$/, "");
}

function isRootAlias(moduleName: string): boolean {
  return moduleName === "app" || moduleName === "root";
}
