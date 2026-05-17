import { defineCollection } from "astro:content";
import { glob } from "astro/loaders";
import { z } from "astro/zod";

const blog = defineCollection({
  loader: glob({
    base: "./src/content/blog",
    pattern: "**/*.{md,mdx}",
  }),
  schema: z.object({
    title: z.string(),
    description: z.string(),
    pubDate: z.coerce.date(),
    updatedDate: z.coerce.date().optional(),
    author: z.string().default("Promedico Wellness Group"),
    category: z
      .enum(["Ear Care", "Portsmouth Ear Care", "Southampton Ear Care"])
      .default("Ear Care"),
    tags: z.array(z.string()).default([]),
    draft: z.boolean().default(false),
    featured: z.boolean().default(false),
    serviceLink: z
      .object({
        label: z.string(),
        href: z.string(),
      })
      .optional(),
  }),
});

export const collections = { blog };
