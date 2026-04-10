import { bootstrapMarkoInertiaReact } from "../../vendor/marko/inertia-react/resources/js/bootstrap";

const pages = import.meta.glob([
  "./pages/**/*.jsx",
  "./pages/**/*.tsx",
  "../../app/**/resources/js/pages/**/*.jsx",
  "../../app/**/resources/js/pages/**/*.tsx",
  "../../modules/**/resources/js/pages/**/*.jsx",
  "../../modules/**/resources/js/pages/**/*.tsx",
  "../../vendor/marko/**/resources/js/pages/**/*.jsx",
  "../../vendor/marko/**/resources/js/pages/**/*.tsx",
]);

bootstrapMarkoInertiaReact({ pages });
