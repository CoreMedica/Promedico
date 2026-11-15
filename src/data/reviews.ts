export type ReviewSource = "Google" | "Fresha";

export interface Review {
  name: string;
  rating: 1 | 2 | 3 | 4 | 5;
  text: string; // use a blank line (\n\n) to split into paragraphs
  source: ReviewSource;
}

// The first review in this list is shown as the large featured card.
export const reviews: Review[] = [
  {
    name: "Dave",
    rating: 5,
    text: "After being let down via Specsavers (audio appointment refused after booked the week before!) Contacted Jacqueline the same day and booked an appointment for my partner the for next day. Partner were put at ease and pleased over her appointment! Highly recommend Jacqueline and she even recommended after care advice for the future appointments too!Well deserved praise!!",
    source: "Google",
  },
  {
    name: "Shaun",
    rating: 5,
    text: "Excellent professional service with a caring clinician. Highly recommended.",
    source: "Fresha",
  },
  {
    name: "Lee",
    rating: 5,
    text: "Jacqueline was very caring, professional and made you feel at ease.\nNo discomfort whatsoever.\nHighly recommended.\nMany Thanks 😎",
    source: "Fresha",
  },
  {
    name: "Ronniemay",
    rating: 5,
    text: "Made you feel at ease and explained things as she was going along very patient and helpful and answered my questions. Would highly recommend.",
    source: "Fresha",
  },
  {
    name: "Vicki",
    rating: 5,
    text: "First time using Promedico Ear Clinic and will certainly be going back for microsuction in my ears. Was made to feel at ease from start to finish.",
    source: "Fresha",
  },
  {
    name: "Lawrence",
    rating: 5,
    text: "excellent service by experienced friendly professional",
    source: "Google",
  },
  {
    name: "Lynda",
    rating: 5,
    text: "Lynda was friendly & explained the procedure thoroughly. She stopped several times to check I was ok and not in any discomfort\nI would recommend this to anyone who is experiencing wax issues causing them to be hard of hearing\nIts an odd feeling but well worth it\nThank you Lynda!!!",
    source: "Google",
  },
  {
    name: "Ron",
    rating: 5,
    text: "Excellent service - very professional and friendly as always. I wouldn't use any other.",
    source: "Google",
  },
  {
    name: "Donald",
    rating: 5,
    text: "Yes, Jackie is a very nice person and extremely good with the customers, I liked this service and will be going back in a year's time. It's a painless procedure and I certainly can hear a bit better, I feel as if I was running around with bunged up ears for a long time and the microsuction certainly sorted that. Well done Jackie and thank you.",
    source: "Google",
  },
];
