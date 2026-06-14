export const pageMotion = {
  initial: { opacity: 0, y: 18 },
  animate: { opacity: 1, y: 0 },
  transition: { duration: 0.45, ease: [0.22, 1, 0.36, 1] },
};

export const listMotion = {
  hidden: { opacity: 0 },
  show: {
    opacity: 1,
    transition: {
      staggerChildren: 0.07,
      delayChildren: 0.08,
    },
  },
};

export const cardMotion = {
  hidden: { opacity: 0, y: 18 },
  show: { opacity: 1, y: 0, transition: { duration: 0.32 } },
  hover: {
    scale: 1.03,
    y: -5,
    boxShadow: '0 24px 80px rgba(34, 211, 238, 0.16)',
  },
};

export const buttonTap = {
  whileTap: { scale: 0.96 },
};
