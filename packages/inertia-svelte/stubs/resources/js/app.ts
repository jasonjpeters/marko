import { bootstrapMarkoInertiaSvelte } from "../../vendor/marko/inertia-svelte/resources/js/bootstrap";

const pages = import.meta.glob([
  "./pages/**/*.svelte",
  "../../app/**/resources/js/pages/**/*.svelte",
  "../../modules/**/resources/js/pages/**/*.svelte",
  "../../vendor/marko/**/resources/js/pages/**/*.svelte",
]);

bootstrapMarkoInertiaSvelte({ pages });
