import { mkdir, copyFile, access } from "node:fs/promises";
import path from "node:path";

const icons = [
  "arrow-right",
  "arrow-up-right",
  "banknote",
  "bed-double",
  "badge-check",
  "briefcase",
  "briefcase-medical",
  "building-2",
  "calendar-days",
  "check",
  "check-circle",
  "chevron-down",
  "chevron-right",
  "clipboard-list",
  "circle-play",
  "download",
  "facebook",
  "filter",
  "graduation-cap",
  "heart",
  "hospital",
  "house",
  "info",
  "instagram",
  "linkedin",
  "list-filter",
  "mail",
  "map-pin",
  "map-pinned",
  "maximize",
  "menu",
  "message-circle",
  "minus",
  "newspaper",
  "notebook-pen",
  "package-check",
  "phone",
  "plus",
  "search",
  "share-2",
  "star",
  "stethoscope",
  "tag",
  "user-round",
  "x",
];

const cwd = process.cwd();
const sourceDir = path.join(cwd, "node_modules", "lucide-static", "icons");
const targetDir = path.join(cwd, "resources", "icons", "lucide");

await mkdir(targetDir, { recursive: true });

for (const icon of icons) {
  const source = path.join(sourceDir, `${icon}.svg`);
  const destination = path.join(targetDir, `${icon}.svg`);
  try {
    await access(source);
  } catch (error) {
    try {
      await access(destination);
      continue;
    } catch (destinationError) {
      // Fall through to the missing icon warning below.
    }

    console.warn(`Skipping missing Lucide icon: ${icon}`);
    continue;
  }

  await copyFile(source, destination);
}

console.log(`Synced ${icons.length} Lucide icons to resources/icons/lucide`);
