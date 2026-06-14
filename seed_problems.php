<?php
// seed_problems.php — Run once: php seed_problems.php
require_once __DIR__ . '/config/db.php';

$pdo->exec("ALTER TABLE problems ADD COLUMN IF NOT EXISTS companies VARCHAR(255) DEFAULT NULL");

$problems = [

// ── EASY ────────────────────────────────────────────────────────────────────

['title'=>'Contains Duplicate','slug'=>'contains-duplicate','difficulty'=>'Easy',
'desc'=>'Given an array of integers, return true if any value appears at least twice, false if every element is distinct.

Input: space-separated integers on one line.
Output: true or false',
'examples'=>'Input: 1 2 3 1
Output: true

Input: 1 2 3 4
Output: false',
'constraints'=>'1 <= n <= 10^5, -10^9 <= nums[i] <= 10^9',
'tests'=>[['input'=>'1 2 3 1','out'=>'true'],['input'=>'1 2 3 4','out'=>'false'],['input'=>'1 1 1 3 3 4 3 2 4 2','out'=>'true']],
'tags'=>'arrays,hash-map','companies'=>'google,amazon,microsoft'],

['title'=>'Valid Anagram','slug'=>'valid-anagram','difficulty'=>'Easy',
'desc'=>'Given two strings s and t, return true if t is an anagram of s, false otherwise.

Input: two strings on separate lines.
Output: true or false',
'examples'=>'Input:
anagram
nagaram
Output: true',
'constraints'=>'1 <= s.length, t.length <= 5 * 10^4. Lowercase English letters only.',
'tests'=>[['input'=>"anagram\nnagaram",'out'=>'true'],['input'=>"rat\ncar",'out'=>'false'],['input'=>"listen\nsilent",'out'=>'true']],
'tags'=>'strings,hash-map','companies'=>'amazon,microsoft,google'],

['title'=>'Missing Number','slug'=>'missing-number','difficulty'=>'Easy',
'desc'=>'Given an array containing n distinct numbers in range [0, n], return the missing number.

Input: space-separated integers.
Output: the missing number.',
'examples'=>'Input: 3 0 1
Output: 2',
'constraints'=>'n == nums.length, 1 <= n <= 10^4, 0 <= nums[i] <= n, all numbers are unique.',
'tests'=>[['input'=>'3 0 1','out'=>'2'],['input'=>'0 1','out'=>'2'],['input'=>'9 6 4 2 3 5 7 0 1','out'=>'8']],
'tags'=>'arrays,math,bit-manipulation','companies'=>'amazon,google,microsoft'],

['title'=>'Single Number','slug'=>'single-number','difficulty'=>'Easy',
'desc'=>'Every element appears twice except for one. Find that single element.

Input: space-separated integers.
Output: the single number.',
'examples'=>'Input: 2 2 1
Output: 1',
'constraints'=>'1 <= n <= 3 * 10^4. Each element appears exactly twice, except for one.',
'tests'=>[['input'=>'2 2 1','out'=>'1'],['input'=>'4 1 2 1 2','out'=>'4'],['input'=>'1','out'=>'1']],
'tags'=>'arrays,bit-manipulation','companies'=>'amazon,google'],

['title'=>'Maximum Subarray','slug'=>'maximum-subarray','difficulty'=>'Easy',
'desc'=>'Find the contiguous subarray with the largest sum (at least one element).

Input: space-separated integers on one line.
Output: the maximum sum.',
'examples'=>'Input: -2 1 -3 4 -1 2 1 -5 4
Output: 6',
'constraints'=>'1 <= n <= 10^5, -10^4 <= nums[i] <= 10^4',
'tests'=>[['input'=>'-2 1 -3 4 -1 2 1 -5 4','out'=>'6'],['input'=>'1','out'=>'1'],['input'=>'5 4 -1 7 8','out'=>'23']],
'tags'=>'arrays,dynamic-programming','companies'=>'amazon,google,microsoft,apple'],

['title'=>'Best Time to Buy and Sell Stock','slug'=>'best-time-to-buy-sell-stock','difficulty'=>'Easy',
'desc'=>'Given prices[i] is the price on day i. Choose a single day to buy and a later day to sell to maximize profit. Return 0 if no profit.

Input: space-separated prices.
Output: maximum profit.',
'examples'=>'Input: 7 1 5 3 6 4
Output: 5',
'constraints'=>'1 <= n <= 10^5, 0 <= prices[i] <= 10^4',
'tests'=>[['input'=>'7 1 5 3 6 4','out'=>'5'],['input'=>'7 6 4 3 1','out'=>'0'],['input'=>'2 4 1','out'=>'2']],
'tags'=>'arrays,greedy','companies'=>'amazon,google,meta,microsoft,apple'],

['title'=>'Climbing Stairs','slug'=>'climbing-stairs','difficulty'=>'Easy',
'desc'=>'You climb a staircase of n steps. Each time you can climb 1 or 2 steps. How many distinct ways to reach the top?

Input: n (integer).
Output: number of distinct ways.',
'examples'=>'Input: 3
Output: 3',
'constraints'=>'1 <= n <= 45',
'tests'=>[['input'=>'2','out'=>'2'],['input'=>'3','out'=>'3'],['input'=>'10','out'=>'89']],
'tags'=>'dynamic-programming','companies'=>'amazon,google,microsoft,apple'],

['title'=>'Move Zeroes','slug'=>'move-zeroes','difficulty'=>'Easy',
'desc'=>'Move all 0s to the end while maintaining relative order of non-zero elements. Modify in place.

Input: space-separated integers.
Output: the resulting array, space-separated.',
'examples'=>'Input: 0 1 0 3 12
Output: 1 3 12 0 0',
'constraints'=>'1 <= n <= 10^4, -2^31 <= nums[i] <= 2^31 - 1',
'tests'=>[['input'=>'0 1 0 3 12','out'=>'1 3 12 0 0'],['input'=>'0','out'=>'0'],['input'=>'1 0 0 2 3','out'=>'1 2 3 0 0']],
'tags'=>'arrays,two-pointers','companies'=>'meta,microsoft'],

['title'=>'Plus One','slug'=>'plus-one','difficulty'=>'Easy',
'desc'=>'Given a large integer as an array of digits, increment by one and return the resulting array.

Input: space-separated digits.
Output: resulting digits, space-separated.',
'examples'=>'Input: 1 2 3
Output: 1 2 4',
'constraints'=>'1 <= digits.length <= 100, 0 <= digits[i] <= 9',
'tests'=>[['input'=>'1 2 3','out'=>'1 2 4'],['input'=>'4 3 2 1','out'=>'4 3 2 2'],['input'=>'9 9 9','out'=>'1 0 0 0']],
'tags'=>'arrays,math','companies'=>'google,amazon'],

['title'=>'Happy Number','slug'=>'happy-number','difficulty'=>'Easy',
'desc'=>'A happy number: repeatedly replace it with the sum of squares of its digits until it equals 1 (happy) or loops forever (not happy). Return true or false.

Input: n (integer).
Output: true or false.',
'examples'=>'Input: 19
Output: true',
'constraints'=>'1 <= n <= 2^31 - 1',
'tests'=>[['input'=>'19','out'=>'true'],['input'=>'2','out'=>'false'],['input'=>'1','out'=>'true']],
'tags'=>'math,hash-map','companies'=>'amazon,google'],

['title'=>'Count Primes','slug'=>'count-primes','difficulty'=>'Easy',
'desc'=>'Count the number of prime numbers strictly less than n.

Input: n (integer).
Output: count of primes.',
'examples'=>'Input: 10
Output: 4',
'constraints'=>'0 <= n <= 5 * 10^6',
'tests'=>[['input'=>'10','out'=>'4'],['input'=>'0','out'=>'0'],['input'=>'100','out'=>'25']],
'tags'=>'math','companies'=>'amazon,microsoft'],

['title'=>'Power of Two','slug'=>'power-of-two','difficulty'=>'Easy',
'desc'=>'Given an integer n, return true if it is a power of two.

Input: n (integer).
Output: true or false.',
'examples'=>'Input: 16
Output: true',
'constraints'=>'-2^31 <= n <= 2^31 - 1',
'tests'=>[['input'=>'1','out'=>'true'],['input'=>'16','out'=>'true'],['input'=>'3','out'=>'false']],
'tags'=>'math,bit-manipulation','companies'=>'google,amazon'],

['title'=>'Roman to Integer','slug'=>'roman-to-integer','difficulty'=>'Easy',
'desc'=>'Convert a Roman numeral string to an integer. Symbols: I=1 V=5 X=10 L=50 C=100 D=500 M=1000. Subtraction applies when a smaller symbol precedes a larger one.

Input: Roman numeral string.
Output: integer.',
'examples'=>'Input: III
Output: 3

Input: MCMXCIV
Output: 1994',
'constraints'=>'1 <= s.length <= 15. s contains only I, V, X, L, C, D, M.',
'tests'=>[['input'=>'III','out'=>'3'],['input'=>'LVIII','out'=>'58'],['input'=>'MCMXCIV','out'=>'1994']],
'tags'=>'strings,hash-map','companies'=>'amazon,google,meta,microsoft'],

['title'=>'Length of Last Word','slug'=>'length-of-last-word','difficulty'=>'Easy',
'desc'=>'Given a string of words separated by spaces, return the length of the last word.

Input: a string (may have trailing spaces).
Output: length of the last word.',
'examples'=>'Input: Hello World
Output: 5',
'constraints'=>'1 <= s.length <= 10^4. s consists of English letters and spaces.',
'tests'=>[['input'=>'Hello World','out'=>'5'],['input'=>'   fly me   to   the moon  ','out'=>'4'],['input'=>'luffy is still joyboy','out'=>'6']],
'tags'=>'strings','companies'=>'google,amazon'],

['title'=>'Majority Element','slug'=>'majority-element','difficulty'=>'Easy',
'desc'=>'Find the element that appears more than n/2 times. It is guaranteed to always exist.

Input: space-separated integers.
Output: the majority element.',
'examples'=>'Input: 3 2 3
Output: 3',
'constraints'=>'n >= 1, -10^9 <= nums[i] <= 10^9',
'tests'=>[['input'=>'3 2 3','out'=>'3'],['input'=>'2 2 1 1 1 2 2','out'=>'2'],['input'=>'6','out'=>'6']],
'tags'=>'arrays,hash-map','companies'=>'amazon,google,microsoft'],

['title'=>'Number of 1 Bits','slug'=>'number-of-1-bits','difficulty'=>'Easy',
'desc'=>'Return the number of 1 bits (Hamming weight) in the binary representation of an unsigned integer.

Input: a non-negative integer.
Output: number of 1 bits.',
'examples'=>'Input: 11
Output: 3',
'constraints'=>'0 <= n <= 2^31 - 1',
'tests'=>[['input'=>'11','out'=>'3'],['input'=>'128','out'=>'1'],['input'=>'4294967293','out'=>'31']],
'tags'=>'bit-manipulation','companies'=>'apple,amazon'],

['title'=>'Intersection of Two Arrays','slug'=>'intersection-of-two-arrays','difficulty'=>'Easy',
'desc'=>'Given two arrays, return their intersection (each element in result must be unique). Result can be in any order.

Input: line 1 = first array (space-separated), line 2 = second array (space-separated).
Output: unique intersection elements, space-separated in ascending order.',
'examples'=>'Input:
1 2 2 1
2 2
Output: 2',
'constraints'=>'1 <= nums.length <= 1000, 0 <= nums[i] <= 1000',
'tests'=>[['input'=>"1 2 2 1\n2 2",'out'=>'2'],['input'=>"4 9 5\n9 4 9 8 4",'out'=>'4 9'],['input'=>"1 2 3\n4 5 6",'out'=>'']],
'tags'=>'arrays,sets','companies'=>'google,meta'],

['title'=>'Ransom Note','slug'=>'ransom-note','difficulty'=>'Easy',
'desc'=>'Given two strings ransomNote and magazine, return true if ransomNote can be constructed using the letters from magazine.

Input: line 1 = ransomNote, line 2 = magazine.
Output: true or false.',
'examples'=>'Input:
aa
aab
Output: true',
'constraints'=>'1 <= lengths <= 10^5. Only lowercase letters.',
'tests'=>[['input'=>"a\nb",'out'=>'false'],['input'=>"aa\nab",'out'=>'false'],['input'=>"aa\naab",'out'=>'true']],
'tags'=>'strings,hash-map','companies'=>'amazon,google'],

['title'=>'First Unique Character in a String','slug'=>'first-unique-character','difficulty'=>'Easy',
'desc'=>'Find the first non-repeating character and return its index. Return -1 if none.

Input: a lowercase string.
Output: the index (0-based).',
'examples'=>'Input: leetcode
Output: 0',
'constraints'=>'1 <= s.length <= 10^5. Only lowercase English letters.',
'tests'=>[['input'=>'leetcode','out'=>'0'],['input'=>'loveleetcode','out'=>'2'],['input'=>'aabb','out'=>'-1']],
'tags'=>'strings,hash-map','companies'=>'amazon,google,microsoft'],

['title'=>'Longest Common Prefix','slug'=>'longest-common-prefix','difficulty'=>'Easy',
'desc'=>'Find the longest common prefix string among an array of strings. Return "" if none.

Input: words on one line, space-separated.
Output: the longest common prefix (empty line if none).',
'examples'=>'Input: flower flow flight
Output: fl',
'constraints'=>'1 <= strs.length <= 200, 0 <= strs[i].length <= 200',
'tests'=>[['input'=>'flower flow flight','out'=>'fl'],['input'=>'dog racecar car','out'=>''],['input'=>'interview inter internal','out'=>'inter']],
'tags'=>'strings','companies'=>'google,amazon'],

['title'=>'Merge Sorted Array','slug'=>'merge-sorted-array','difficulty'=>'Easy',
'desc'=>'Merge nums2 into nums1 in-place. nums1 has length m+n, last n elements are 0.

Input: line 1 = m and n space-separated, line 2 = nums1 (m+n values), line 3 = nums2 (n values).
Output: merged sorted array, space-separated.',
'examples'=>'Input:
3 3
1 2 3 0 0 0
2 5 6
Output: 1 2 2 3 5 6',
'constraints'=>'0 <= m, n <= 200',
'tests'=>[['input'=>"3 3\n1 2 3 0 0 0\n2 5 6",'out'=>'1 2 2 3 5 6'],['input'=>"1 0\n1\n",'out'=>'1'],['input'=>"0 1\n0\n1",'out'=>'1']],
'tags'=>'arrays,two-pointers','companies'=>'amazon,google,meta,microsoft'],

['title'=>'Search Insert Position','slug'=>'search-insert-position','difficulty'=>'Easy',
'desc'=>'Given a sorted array and a target, return the index if found, otherwise the index where it would be inserted.

Input: line 1 = sorted array (space-separated), line 2 = target.
Output: index.',
'examples'=>'Input:
1 3 5 6
5
Output: 2',
'constraints'=>'1 <= n <= 10^4. Distinct values. -10^4 <= nums[i], target <= 10^4.',
'tests'=>[['input'=>"1 3 5 6\n5",'out'=>'2'],['input'=>"1 3 5 6\n2",'out'=>'1'],['input'=>"1 3 5 6\n7",'out'=>'4']],
'tags'=>'arrays,binary-search','companies'=>'google,amazon'],

['title'=>'Remove Element','slug'=>'remove-element','difficulty'=>'Easy',
'desc'=>'Remove all occurrences of val from nums in-place. Return the count k of remaining elements. Print the first k elements.

Input: line 1 = array (space-separated), line 2 = val.
Output: count k, then first k elements space-separated.',
'examples'=>'Input:
3 2 2 3
3
Output:
2
2 2',
'constraints'=>'0 <= n <= 100, 0 <= val <= 100',
'tests'=>[['input'=>"3 2 2 3\n3",'out'=>"2\n2 2"],['input'=>"0 1 2 2 3 0 4 2\n2",'out'=>"5\n0 1 3 0 4"],['input'=>"1\n1",'out'=>"0\n"]],
'tags'=>'arrays,two-pointers','companies'=>'google,amazon'],

['title'=>'Squares of a Sorted Array','slug'=>'squares-of-sorted-array','difficulty'=>'Easy',
'desc'=>'Return the squares of a sorted array, also sorted in non-decreasing order.

Input: space-separated integers (sorted in non-decreasing order).
Output: squares in non-decreasing order, space-separated.',
'examples'=>'Input: -4 -1 0 3 10
Output: 0 1 9 16 100',
'constraints'=>'1 <= n <= 10^4, -10^4 <= nums[i] <= 10^4, nums is sorted.',
'tests'=>[['input'=>'-4 -1 0 3 10','out'=>'0 1 9 16 100'],['input'=>'-7 -3 2 3 11','out'=>'4 9 9 49 121'],['input'=>'1 2 3','out'=>'1 4 9']],
'tags'=>'arrays,two-pointers','companies'=>'google,amazon'],

['title'=>'Add Binary','slug'=>'add-binary','difficulty'=>'Easy',
'desc'=>'Given two binary strings a and b, return their sum as a binary string.

Input: line 1 = a, line 2 = b.
Output: binary sum.',
'examples'=>'Input:
11
1
Output: 100',
'constraints'=>'1 <= a.length, b.length <= 10^4. No leading zeros except "0" itself.',
'tests'=>[['input'=>"11\n1",'out'=>'100'],['input'=>"1010\n1011",'out'=>'10101'],['input'=>"0\n0",'out'=>'0']],
'tags'=>'strings,math','companies'=>'google,amazon,meta'],

['title'=>'Valid Palindrome','slug'=>'valid-palindrome','difficulty'=>'Easy',
'desc'=>'A string is a palindrome if it reads the same forward and backward, considering only alphanumeric characters (case-insensitive).

Input: a string.
Output: true or false.',
'examples'=>'Input: A man a plan a canal Panama
Output: true',
'constraints'=>'1 <= s.length <= 2 * 10^5',
'tests'=>[['input'=>'A man a plan a canal Panama','out'=>'true'],['input'=>'race a car','out'=>'false'],['input'=>' ','out'=>'true']],
'tags'=>'strings,two-pointers','companies'=>'amazon,google,microsoft,meta'],

['title'=>'Reverse Integer','slug'=>'reverse-integer','difficulty'=>'Easy',
'desc'=>'Reverse the digits of a 32-bit signed integer. Return 0 if the reversed value overflows [-2^31, 2^31-1].

Input: an integer.
Output: reversed integer (or 0 on overflow).',
'examples'=>'Input: 123
Output: 321',
'constraints'=>'-2^31 <= x <= 2^31 - 1',
'tests'=>[['input'=>'123','out'=>'321'],['input'=>'-123','out'=>'-321'],['input'=>'120','out'=>'21']],
'tags'=>'math','companies'=>'amazon,google,microsoft'],

['title'=>'Count and Say','slug'=>'count-and-say','difficulty'=>'Easy',
'desc'=>'Generate the nth term of the count-and-say sequence. 1→"1", 2→"11", 3→"21", 4→"1211", etc.

Input: n (integer, 1-indexed).
Output: the nth term as a string.',
'examples'=>'Input: 4
Output: 1211',
'constraints'=>'1 <= n <= 30',
'tests'=>[['input'=>'1','out'=>'1'],['input'=>'4','out'=>'1211'],['input'=>'6','out'=>'312211']],
'tags'=>'strings','companies'=>'amazon,google'],

['title'=>'Min Stack','slug'=>'min-stack','difficulty'=>'Easy',
'desc'=>'Implement a stack that supports push, pop, top, and getMin in O(1).

Input: operations, one per line: "push X", "pop", "top", "getMin". Print result for top and getMin.
Output: result of each top/getMin call, one per line.',
'examples'=>'Input:
push -2
push 0
push -3
getMin
pop
top
getMin
Output:
-3
0
-2',
'constraints'=>'All operations are valid. -2^31 <= val <= 2^31 - 1',
'tests'=>[['input'=>"push -2\npush 0\npush -3\ngetMin\npop\ntop\ngetMin",'out'=>"-3\n0\n-2"],['input'=>"push 5\npush 3\ngetMin\npop\ngetMin",'out'=>"3\n5"],['input'=>"push 1\ntop\ngetMin",'out'=>"1\n1"]],
'tags'=>'stack','companies'=>'amazon,google,microsoft,meta'],

// ── MEDIUM ──────────────────────────────────────────────────────────────────

['title'=>'Longest Substring Without Repeating Characters','slug'=>'longest-substring-no-repeat','difficulty'=>'Medium',
'desc'=>'Find the length of the longest substring without repeating characters.

Input: a string.
Output: length.',
'examples'=>'Input: abcabcbb
Output: 3',
'constraints'=>'0 <= s.length <= 5 * 10^4. English letters, digits, symbols, and spaces.',
'tests'=>[['input'=>'abcabcbb','out'=>'3'],['input'=>'bbbbb','out'=>'1'],['input'=>'pwwkew','out'=>'3']],
'tags'=>'strings,sliding-window,hash-map','companies'=>'amazon,google,meta,microsoft,apple'],

['title'=>'Container With Most Water','slug'=>'container-with-most-water','difficulty'=>'Medium',
'desc'=>'Given n heights, find two lines that together with the x-axis forms a container holding the most water.

Input: space-separated heights.
Output: maximum water.',
'examples'=>'Input: 1 8 6 2 5 4 8 3 7
Output: 49',
'constraints'=>'n >= 2, 0 <= height[i] <= 10^4',
'tests'=>[['input'=>'1 8 6 2 5 4 8 3 7','out'=>'49'],['input'=>'1 1','out'=>'1'],['input'=>'4 3 2 1 4','out'=>'16']],
'tags'=>'arrays,two-pointers','companies'=>'amazon,google,meta,microsoft'],

['title'=>'3Sum','slug'=>'3sum','difficulty'=>'Medium',
'desc'=>'Find all unique triplets that sum to zero.

Input: space-separated integers.
Output: each triplet on its own line, elements space-separated in ascending order. Triplets in lexicographic order. Empty line if no result.',
'examples'=>'Input: -1 0 1 2 -1 -4
Output:
-1 -1 2
-1 0 1',
'constraints'=>'0 <= n <= 3000, -10^5 <= nums[i] <= 10^5',
'tests'=>[['input'=>'-1 0 1 2 -1 -4','out'=>"-1 -1 2\n-1 0 1"],['input'=>'0 1 1','out'=>''],['input'=>'0 0 0','out'=>'0 0 0']],
'tags'=>'arrays,two-pointers','companies'=>'amazon,google,meta,microsoft,apple'],

['title'=>'Longest Palindromic Substring','slug'=>'longest-palindromic-substring','difficulty'=>'Medium',
'desc'=>'Return the longest palindromic substring of s.

Input: a string.
Output: the longest palindromic substring (if multiple of same length, return leftmost).',
'examples'=>'Input: babad
Output: bab',
'constraints'=>'1 <= s.length <= 1000. ASCII characters.',
'tests'=>[['input'=>'babad','out'=>'bab'],['input'=>'cbbd','out'=>'bb'],['input'=>'racecar','out'=>'racecar']],
'tags'=>'strings,dynamic-programming','companies'=>'amazon,google,microsoft'],

['title'=>'Group Anagrams','slug'=>'group-anagrams','difficulty'=>'Medium',
'desc'=>'Group anagrams together from an array of strings.

Input: space-separated words.
Output: each group on its own line, words sorted alphabetically within the group, groups sorted by their first word.',
'examples'=>'Input: eat tea tan ate nat bat
Output:
ate eat tea
bat
ant nat tan',
'constraints'=>'1 <= strs.length <= 10^4, 0 <= strs[i].length <= 100',
'tests'=>[['input'=>'eat tea tan ate nat bat','out'=>"ate eat tea\nbat\nant nat tan"],['input'=>'a','out'=>'a'],['input'=>'','out'=>'']],
'tags'=>'strings,hash-map','companies'=>'amazon,google,meta,microsoft'],

['title'=>'Merge Intervals','slug'=>'merge-intervals','difficulty'=>'Medium',
'desc'=>'Merge all overlapping intervals.

Input: each line is an interval "start end". End with blank line or EOF.
Output: merged intervals, one per line.',
'examples'=>'Input:
1 3
2 6
8 10
15 18
Output:
1 6
8 10
15 18',
'constraints'=>'1 <= intervals.length <= 10^4, 0 <= starti <= endi <= 10^4',
'tests'=>[['input'=>"1 3\n2 6\n8 10\n15 18",'out'=>"1 6\n8 10\n15 18"],['input'=>"1 4\n4 5",'out'=>'1 5'],['input'=>"1 4\n2 3",'out'=>'1 4']],
'tags'=>'arrays,sorting','companies'=>'amazon,google,meta,microsoft'],

['title'=>'Product of Array Except Self','slug'=>'product-of-array-except-self','difficulty'=>'Medium',
'desc'=>'Return an array such that output[i] equals the product of all elements except nums[i]. No division allowed. O(n) time.

Input: space-separated integers.
Output: result array space-separated.',
'examples'=>'Input: 1 2 3 4
Output: 24 12 8 6',
'constraints'=>'2 <= n <= 10^5, -30 <= nums[i] <= 30. Guaranteed no overflow.',
'tests'=>[['input'=>'1 2 3 4','out'=>'24 12 8 6'],['input'=>'-1 1 0 -3 3','out'=>'0 0 9 0 0'],['input'=>'2 3','out'=>'3 2']],
'tags'=>'arrays','companies'=>'amazon,google,meta,microsoft'],

['title'=>'Find Minimum in Rotated Sorted Array','slug'=>'find-min-rotated-array','difficulty'=>'Medium',
'desc'=>'Find the minimum element in a rotated sorted array (no duplicates).

Input: space-separated integers.
Output: minimum element.',
'examples'=>'Input: 3 4 5 1 2
Output: 1',
'constraints'=>'n >= 1. Unique elements.',
'tests'=>[['input'=>'3 4 5 1 2','out'=>'1'],['input'=>'4 5 6 7 0 1 2','out'=>'0'],['input'=>'11 13 15 17','out'=>'11']],
'tags'=>'arrays,binary-search','companies'=>'amazon,google,microsoft'],

['title'=>'Search in Rotated Sorted Array','slug'=>'search-rotated-sorted-array','difficulty'=>'Medium',
'desc'=>'Search for a target in a rotated sorted array. Return its index or -1.

Input: line 1 = array, line 2 = target.
Output: index or -1.',
'examples'=>'Input:
4 5 6 7 0 1 2
0
Output: 4',
'constraints'=>'1 <= n <= 5000. Unique values.',
'tests'=>[['input'=>"4 5 6 7 0 1 2\n0",'out'=>'4'],['input'=>"4 5 6 7 0 1 2\n3",'out'=>'-1'],['input'=>"1\n0",'out'=>'-1']],
'tags'=>'arrays,binary-search','companies'=>'amazon,google,meta,microsoft'],

['title'=>'Maximum Product Subarray','slug'=>'maximum-product-subarray','difficulty'=>'Medium',
'desc'=>'Find the contiguous subarray with the largest product.

Input: space-separated integers.
Output: the maximum product.',
'examples'=>'Input: 2 3 -2 4
Output: 6',
'constraints'=>'1 <= n <= 2 * 10^4, -10 <= nums[i] <= 10',
'tests'=>[['input'=>'2 3 -2 4','out'=>'6'],['input'=>'-2 0 -1','out'=>'0'],['input'=>'2 -5 -2 -4 3','out'=>'24']],
'tags'=>'arrays,dynamic-programming','companies'=>'amazon,google,microsoft'],

['title'=>'Coin Change','slug'=>'coin-change','difficulty'=>'Medium',
'desc'=>'Find fewest coins to make amount. Return -1 if impossible.

Input: line 1 = coin denominations (space-separated), line 2 = amount.
Output: fewest number of coins or -1.',
'examples'=>'Input:
1 5 10 25
36
Output: 3',
'constraints'=>'1 <= coins.length <= 12, 1 <= coins[i] <= 2^31-1, 0 <= amount <= 10^4',
'tests'=>[['input'=>"1 5 10 25\n36",'out'=>'3'],['input'=>"2\n3",'out'=>'-1'],['input'=>"1\n0",'out'=>'0']],
'tags'=>'dynamic-programming','companies'=>'amazon,google,microsoft'],

['title'=>'Longest Increasing Subsequence','slug'=>'longest-increasing-subsequence','difficulty'=>'Medium',
'desc'=>'Return the length of the longest strictly increasing subsequence.

Input: space-separated integers.
Output: length.',
'examples'=>'Input: 10 9 2 5 3 7 101 18
Output: 4',
'constraints'=>'1 <= n <= 2500, -10^4 <= nums[i] <= 10^4',
'tests'=>[['input'=>'10 9 2 5 3 7 101 18','out'=>'4'],['input'=>'0 1 0 3 2 3','out'=>'4'],['input'=>'7 7 7 7 7','out'=>'1']],
'tags'=>'dynamic-programming,binary-search','companies'=>'amazon,google,microsoft,apple'],

['title'=>'House Robber','slug'=>'house-robber','difficulty'=>'Medium',
'desc'=>'Adjacent houses cannot both be robbed. Maximize the total amount.

Input: space-separated amounts.
Output: maximum amount.',
'examples'=>'Input: 2 7 9 3 1
Output: 12',
'constraints'=>'1 <= n <= 100, 0 <= nums[i] <= 400',
'tests'=>[['input'=>'1 2 3 1','out'=>'4'],['input'=>'2 7 9 3 1','out'=>'12'],['input'=>'0','out'=>'0']],
'tags'=>'dynamic-programming','companies'=>'amazon,google,microsoft'],

['title'=>'Jump Game','slug'=>'jump-game','difficulty'=>'Medium',
'desc'=>'Each element is your max jump length. Return true if you can reach the last index.

Input: space-separated integers.
Output: true or false.',
'examples'=>'Input: 2 3 1 1 4
Output: true',
'constraints'=>'1 <= n <= 3 * 10^4, 0 <= nums[i] <= 10^5',
'tests'=>[['input'=>'2 3 1 1 4','out'=>'true'],['input'=>'3 2 1 0 4','out'=>'false'],['input'=>'0','out'=>'true']],
'tags'=>'arrays,greedy','companies'=>'amazon,google,microsoft'],

['title'=>'Unique Paths','slug'=>'unique-paths','difficulty'=>'Medium',
'desc'=>'Count paths from top-left to bottom-right of an m×n grid, moving only right or down.

Input: m and n space-separated.
Output: number of paths.',
'examples'=>'Input: 3 7
Output: 28',
'constraints'=>'1 <= m, n <= 100',
'tests'=>[['input'=>'3 7','out'=>'28'],['input'=>'3 2','out'=>'3'],['input'=>'1 1','out'=>'1']],
'tags'=>'dynamic-programming,math','companies'=>'amazon,google,microsoft'],

['title'=>'Decode Ways','slug'=>'decode-ways','difficulty'=>'Medium',
'desc'=>'A digit string can map A=1...Z=26. Count all possible decodings.

Input: digit string.
Output: number of decodings.',
'examples'=>'Input: 12
Output: 2',
'constraints'=>'1 <= s.length <= 100. s contains digits, may have leading zeros.',
'tests'=>[['input'=>'12','out'=>'2'],['input'=>'226','out'=>'3'],['input'=>'06','out'=>'0']],
'tags'=>'strings,dynamic-programming','companies'=>'amazon,meta,microsoft'],

['title'=>'Word Break','slug'=>'word-break','difficulty'=>'Medium',
'desc'=>'Given a string s and a dictionary, return true if s can be segmented into dictionary words.

Input: line 1 = string s, line 2 = dictionary words space-separated.
Output: true or false.',
'examples'=>'Input:
leetcode
leet code
Output: true',
'constraints'=>'1 <= s.length <= 300, dictionary size <= 1000',
'tests'=>[['input'=>"leetcode\nleet code",'out'=>'true'],['input'=>"applepenapple\napple pen",'out'=>'true'],['input'=>"catsandog\ncats dog sand and cat",'out'=>'false']],
'tags'=>'strings,dynamic-programming,hash-map','companies'=>'amazon,google,meta,microsoft'],

['title'=>'Combination Sum','slug'=>'combination-sum','difficulty'=>'Medium',
'desc'=>'Find all unique combinations of candidates that sum to target. Same number may be used unlimited times.

Input: line 1 = candidates (space-separated), line 2 = target.
Output: each combination on its own line, elements sorted ascending, combinations in lexicographic order.',
'examples'=>'Input:
2 3 6 7
7
Output:
2 2 3
7',
'constraints'=>'1 <= candidates.length <= 30, 1 <= candidates[i] <= 200, target <= 500',
'tests'=>[['input'=>"2 3 6 7\n7",'out'=>"2 2 3\n7"],['input'=>"2 3 5\n8",'out'=>"2 2 2 2\n2 3 3\n3 5"],['input'=>"2\n1",'out'=>'']],
'tags'=>'arrays,backtracking','companies'=>'amazon,google,meta'],

['title'=>'Permutations','slug'=>'permutations','difficulty'=>'Medium',
'desc'=>'Return all permutations of a distinct integer array.

Input: space-separated distinct integers.
Output: each permutation on its own line, elements space-separated. Permutations in lexicographic order.',
'examples'=>'Input: 1 2 3
Output:
1 2 3
1 3 2
2 1 3
2 3 1
3 1 2
3 2 1',
'constraints'=>'1 <= n <= 6, -10 <= nums[i] <= 10. All unique.',
'tests'=>[['input'=>'1 2 3','out'=>"1 2 3\n1 3 2\n2 1 3\n2 3 1\n3 1 2\n3 2 1"],['input'=>'0 1','out'=>"0 1\n1 0"],['input'=>'1','out'=>'1']],
'tags'=>'arrays,backtracking','companies'=>'amazon,google,meta,microsoft'],

['title'=>'Subsets','slug'=>'subsets','difficulty'=>'Medium',
'desc'=>'Return all possible subsets of a distinct integer array.

Input: space-separated distinct integers (sorted ascending).
Output: each subset on its own line, elements space-separated, subsets in order from smallest to largest.',
'examples'=>'Input: 1 2 3
Output:

1
2
3
1 2
1 3
2 3
1 2 3',
'constraints'=>'1 <= n <= 10, -10 <= nums[i] <= 10. All unique.',
'tests'=>[['input'=>'1 2 3','out'=>"\n1\n2\n3\n1 2\n1 3\n2 3\n1 2 3"],['input'=>'0','out'=>"\n0"],['input'=>'1 2','out'=>"\n1\n2\n1 2"]],
'tags'=>'arrays,backtracking','companies'=>'amazon,google,meta'],

['title'=>'Spiral Matrix','slug'=>'spiral-matrix','difficulty'=>'Medium',
'desc'=>'Return all elements of an m×n matrix in spiral order.

Input: line 1 = m and n, then m lines each with n space-separated integers.
Output: elements in spiral order, space-separated.',
'examples'=>'Input:
3 3
1 2 3
4 5 6
7 8 9
Output: 1 2 3 6 9 8 7 4 5',
'constraints'=>'m, n >= 1, -100 <= matrix[i][j] <= 100',
'tests'=>[['input'=>"3 3\n1 2 3\n4 5 6\n7 8 9",'out'=>'1 2 3 6 9 8 7 4 5'],['input'=>"3 4\n1 2 3 4\n5 6 7 8\n9 10 11 12",'out'=>'1 2 3 4 8 12 11 10 9 5 6 7'],['input'=>"1 1\n42",'out'=>'42']],
'tags'=>'arrays','companies'=>'amazon,google,meta,microsoft'],

['title'=>'Set Matrix Zeroes','slug'=>'set-matrix-zeroes','difficulty'=>'Medium',
'desc'=>'If an element is 0, set its entire row and column to 0. Do in-place.

Input: line 1 = m and n, then m lines of n space-separated integers.
Output: modified matrix, same format.',
'examples'=>'Input:
3 3
1 1 1
1 0 1
1 1 1
Output:
1 0 1
0 0 0
1 0 1',
'constraints'=>'1 <= m, n <= 200, -2^31 <= matrix[i][j] <= 2^31-1',
'tests'=>[['input'=>"3 3\n1 1 1\n1 0 1\n1 1 1",'out'=>"1 0 1\n0 0 0\n1 0 1"],['input'=>"3 4\n0 1 2 0\n3 4 5 2\n1 3 1 5",'out'=>"0 0 0 0\n0 4 5 0\n0 3 1 0"],['input'=>"1 1\n1",'out'=>'1']],
'tags'=>'arrays','companies'=>'amazon,google,meta'],

['title'=>'Rotate Image','slug'=>'rotate-image','difficulty'=>'Medium',
'desc'=>'Rotate an n×n matrix 90 degrees clockwise in-place.

Input: line 1 = n, then n lines of n space-separated integers.
Output: rotated matrix, same format.',
'examples'=>'Input:
3
1 2 3
4 5 6
7 8 9
Output:
7 4 1
8 5 2
9 6 3',
'constraints'=>'n >= 1, -1000 <= matrix[i][j] <= 1000',
'tests'=>[['input'=>"3\n1 2 3\n4 5 6\n7 8 9",'out'=>"7 4 1\n8 5 2\n9 6 3"],['input'=>"4\n5 1 9 11\n2 4 8 10\n13 3 6 7\n15 14 12 16",'out'=>"15 13 2 5\n14 3 4 1\n12 6 8 9\n16 7 10 11"],['input'=>"1\n5",'out'=>'5']],
'tags'=>'arrays','companies'=>'amazon,google,meta,microsoft'],

['title'=>'Number of Islands','slug'=>'number-of-islands','difficulty'=>'Medium',
'desc'=>'Count the number of islands in a 2D grid of 1s (land) and 0s (water).

Input: line 1 = rows and cols, then rows lines of cols space-separated characters (1 or 0).
Output: number of islands.',
'examples'=>'Input:
4 5
1 1 1 1 0
1 1 0 1 0
1 1 0 0 0
0 0 0 0 0
Output: 1',
'constraints'=>'1 <= rows, cols <= 300',
'tests'=>[['input'=>"4 5\n1 1 1 1 0\n1 1 0 1 0\n1 1 0 0 0\n0 0 0 0 0",'out'=>'1'],['input'=>"4 5\n1 1 0 0 0\n1 1 0 0 0\n0 0 1 0 0\n0 0 0 1 1",'out'=>'3'],['input'=>"2 2\n1 0\n0 1",'out'=>'2']],
'tags'=>'graphs,bfs','companies'=>'amazon,google,meta,microsoft,apple'],

['title'=>'Course Schedule','slug'=>'course-schedule','difficulty'=>'Medium',
'desc'=>'Determine if you can finish all courses given prerequisites (no cycles allowed).

Input: line 1 = numCourses, line 2 = number of prereqs, then one "a b" pair per line (b is prerequisite for a).
Output: true or false.',
'examples'=>'Input:
2
1
1 0
Output: true',
'constraints'=>'1 <= numCourses <= 2000, 0 <= prerequisites.length <= 5000',
'tests'=>[['input'=>"2\n1\n1 0",'out'=>'true'],['input'=>"2\n2\n1 0\n0 1",'out'=>'false'],['input'=>"3\n3\n0 1\n0 2\n1 2",'out'=>'true']],
'tags'=>'graphs,topological-sort','companies'=>'amazon,google,meta'],

['title'=>'Top K Frequent Elements','slug'=>'top-k-frequent-elements','difficulty'=>'Medium',
'desc'=>'Return the k most frequent elements. Output in any order.

Input: line 1 = space-separated integers, line 2 = k.
Output: top k elements, space-separated in ascending order.',
'examples'=>'Input:
1 1 1 2 2 3
2
Output: 1 2',
'constraints'=>'1 <= n <= 10^5, k is valid.',
'tests'=>[['input'=>"1 1 1 2 2 3\n2",'out'=>'1 2'],['input'=>"1\n1",'out'=>'1'],['input'=>"4 1 2 2 3 3 3 4 4 4\n2",'out'=>'3 4']],
'tags'=>'arrays,heap,hash-map','companies'=>'amazon,google,meta,microsoft'],

['title'=>'Kth Largest Element in an Array','slug'=>'kth-largest-element','difficulty'=>'Medium',
'desc'=>'Find the kth largest element in an unsorted array (not the kth distinct).

Input: line 1 = space-separated integers, line 2 = k.
Output: kth largest element.',
'examples'=>'Input:
3 2 1 5 6 4
2
Output: 5',
'constraints'=>'1 <= k <= n <= 10^4, -10^4 <= nums[i] <= 10^4',
'tests'=>[['input'=>"3 2 1 5 6 4\n2",'out'=>'5'],['input'=>"3 2 3 1 2 4 5 5 6\n4",'out'=>'4'],['input'=>"1\n1",'out'=>'1']],
'tags'=>'arrays,heap','companies'=>'amazon,google,meta,microsoft'],

['title'=>'Letter Combinations of a Phone Number','slug'=>'letter-combinations-phone','difficulty'=>'Medium',
'desc'=>'Return all possible letter combinations a digit string could represent (T9 mapping: 2=abc, 3=def, 4=ghi, 5=jkl, 6=mno, 7=pqrs, 8=tuv, 9=wxyz). Empty string if digits is empty.

Input: a digit string.
Output: combinations in lexicographic order, space-separated.',
'examples'=>'Input: 23
Output: ad ae af bd be bf cd ce cf',
'constraints'=>'0 <= digits.length <= 4. Digits 2-9.',
'tests'=>[['input'=>'23','out'=>'ad ae af bd be bf cd ce cf'],['input'=>'','out'=>''],['input'=>'2','out'=>'a b c']],
'tags'=>'strings,backtracking','companies'=>'amazon,google,meta'],

['title'=>'Longest Common Subsequence','slug'=>'longest-common-subsequence','difficulty'=>'Medium',
'desc'=>'Return the length of the longest common subsequence of two strings.

Input: line 1 = text1, line 2 = text2.
Output: LCS length.',
'examples'=>'Input:
abcde
ace
Output: 3',
'constraints'=>'1 <= text lengths <= 1000. Lowercase only.',
'tests'=>[['input'=>"abcde\nace",'out'=>'3'],['input'=>"abc\nabc",'out'=>'3'],['input'=>"abc\ndef",'out'=>'0']],
'tags'=>'strings,dynamic-programming','companies'=>'amazon,google,microsoft'],

['title'=>'Edit Distance','slug'=>'edit-distance','difficulty'=>'Medium',
'desc'=>'Find the minimum operations (insert, delete, replace) to convert word1 to word2.

Input: line 1 = word1, line 2 = word2.
Output: minimum edit distance.',
'examples'=>'Input:
horse
ros
Output: 3',
'constraints'=>'0 <= word lengths <= 500. Lowercase letters.',
'tests'=>[['input'=>"horse\nros",'out'=>'3'],['input'=>"intention\nexecution",'out'=>'5'],['input'=>"\n",'out'=>'0']],
'tags'=>'strings,dynamic-programming','companies'=>'amazon,google,microsoft'],

['title'=>'Minimum Path Sum','slug'=>'minimum-path-sum','difficulty'=>'Medium',
'desc'=>'Find the path from top-left to bottom-right with minimum sum (only right or down moves).

Input: line 1 = m and n, then m lines of n space-separated non-negative integers.
Output: minimum path sum.',
'examples'=>'Input:
3 3
1 3 1
1 5 1
4 2 1
Output: 7',
'constraints'=>'1 <= m, n <= 200, 0 <= grid[i][j] <= 200',
'tests'=>[['input'=>"3 3\n1 3 1\n1 5 1\n4 2 1",'out'=>'7'],['input'=>"2 3\n1 2 3\n4 5 6",'out'=>'12'],['input'=>"1 1\n5",'out'=>'5']],
'tags'=>'dynamic-programming','companies'=>'amazon,google,microsoft'],

['title'=>'Partition Equal Subset Sum','slug'=>'partition-equal-subset-sum','difficulty'=>'Medium',
'desc'=>'Determine if the array can be partitioned into two subsets with equal sum.

Input: space-separated positive integers.
Output: true or false.',
'examples'=>'Input: 1 5 11 5
Output: true',
'constraints'=>'1 <= n <= 200, 1 <= nums[i] <= 100',
'tests'=>[['input'=>'1 5 11 5','out'=>'true'],['input'=>'1 2 3 5','out'=>'false'],['input'=>'2 2','out'=>'true']],
'tags'=>'arrays,dynamic-programming','companies'=>'amazon,google,meta'],

['title'=>'K Closest Points to Origin','slug'=>'k-closest-points-to-origin','difficulty'=>'Medium',
'desc'=>'Return the k closest points to the origin (0,0). Output in any order.

Input: line 1 = n and k, then n lines each "x y".
Output: k closest points, each "x y" on its own line, sorted by x then y.',
'examples'=>'Input:
2 1
1 3
-2 2
Output: -2 2',
'constraints'=>'1 <= k <= n <= 10^4, -10^4 <= x, y <= 10^4',
'tests'=>[['input'=>"2 1\n1 3\n-2 2",'out'=>'-2 2'],['input'=>"3 2\n3 3\n5 -1\n-2 4",'out'=>"3 3\n5 -1"],['input'=>"1 1\n0 0",'out'=>'0 0']],
'tags'=>'arrays,heap','companies'=>'amazon,google,meta'],

['title'=>'Maximum Depth of Binary Tree','slug'=>'max-depth-binary-tree','difficulty'=>'Medium',
'desc'=>'Find the maximum depth of a binary tree given as level-order array. -1 means null.

Input: space-separated level-order values (-1 for null).
Output: max depth.',
'examples'=>'Input: 3 9 20 -1 -1 15 7
Output: 3',
'constraints'=>'0 <= n <= 10^4, -100 <= node.val <= 100',
'tests'=>[['input'=>'3 9 20 -1 -1 15 7','out'=>'3'],['input'=>'1 -1 2','out'=>'2'],['input'=>'-1','out'=>'0']],
'tags'=>'trees,bfs','companies'=>'amazon,google,meta'],

['title'=>'Validate Binary Search Tree','slug'=>'validate-bst','difficulty'=>'Medium',
'desc'=>'Validate if a binary tree is a valid BST. Given as level-order array, -1 means null.

Input: space-separated level-order values (-1 for null).
Output: true or false.',
'examples'=>'Input: 2 1 3
Output: true',
'constraints'=>'-2^31 <= node.val <= 2^31-1',
'tests'=>[['input'=>'2 1 3','out'=>'true'],['input'=>'5 1 4 -1 -1 3 6','out'=>'false'],['input'=>'1','out'=>'true']],
'tags'=>'trees','companies'=>'amazon,google,microsoft'],

['title'=>'Binary Tree Level Order Traversal','slug'=>'binary-tree-level-order','difficulty'=>'Medium',
'desc'=>'Return level order traversal of a binary tree. Given as level-order array, -1 means null.

Input: space-separated level-order values (-1 for null).
Output: each level on its own line, values space-separated.',
'examples'=>'Input: 3 9 20 -1 -1 15 7
Output:
3
9 20
15 7',
'constraints'=>'0 <= n <= 2000, -1000 <= node.val <= 1000',
'tests'=>[['input'=>'3 9 20 -1 -1 15 7','out'=>"3\n9 20\n15 7"],['input'=>'1','out'=>'1'],['input'=>'-1','out'=>'']],
'tags'=>'trees,bfs','companies'=>'amazon,google,meta'],

['title'=>'Task Scheduler','slug'=>'task-scheduler','difficulty'=>'Medium',
'desc'=>'Given tasks and cooldown n, find the minimum intervals to finish all tasks.

Input: line 1 = task letters (space-separated), line 2 = n (cooldown).
Output: minimum intervals.',
'examples'=>'Input:
A A A B B B
2
Output: 8',
'constraints'=>'1 <= tasks.length <= 10^4, 0 <= n <= 100',
'tests'=>[['input'=>"A A A B B B\n2",'out'=>'8'],['input'=>"A A A B B B\n0",'out'=>'6'],['input'=>"A A A A B B C C\n3",'out'=>'10']],
'tags'=>'arrays,greedy,heap','companies'=>'amazon,google,meta'],

// ── HARD ────────────────────────────────────────────────────────────────────

['title'=>'Trapping Rain Water','slug'=>'trapping-rain-water','difficulty'=>'Hard',
'desc'=>'Compute how much water can be trapped between bars after raining.

Input: space-separated heights.
Output: total trapped water.',
'examples'=>'Input: 0 1 0 2 1 0 1 3 2 1 2 1
Output: 6',
'constraints'=>'n >= 0, 0 <= height[i] <= 10^5',
'tests'=>[['input'=>'0 1 0 2 1 0 1 3 2 1 2 1','out'=>'6'],['input'=>'4 2 0 3 2 5','out'=>'9'],['input'=>'3 0 3','out'=>'3']],
'tags'=>'arrays,two-pointers,stack','companies'=>'amazon,google,meta,microsoft'],

['title'=>'N-Queens','slug'=>'n-queens','difficulty'=>'Hard',
'desc'=>'Return all distinct solutions to the n-queens puzzle.

Input: n (integer).
Output: number of distinct solutions.',
'examples'=>'Input: 4
Output: 2',
'constraints'=>'1 <= n <= 9',
'tests'=>[['input'=>'1','out'=>'1'],['input'=>'4','out'=>'2'],['input'=>'8','out'=>'92']],
'tags'=>'backtracking','companies'=>'amazon,google,microsoft'],

['title'=>'Minimum Window Substring','slug'=>'minimum-window-substring','difficulty'=>'Hard',
'desc'=>'Find the minimum window substring of s that contains all characters of t. Return "" if impossible.

Input: line 1 = s, line 2 = t.
Output: minimum window, or empty line.',
'examples'=>'Input:
ADOBECODEBANC
ABC
Output: BANC',
'constraints'=>'1 <= s.length <= 10^5, 1 <= t.length <= 10^4',
'tests'=>[['input'=>"ADOBECODEBANC\nABC",'out'=>'BANC'],['input'=>"a\na",'out'=>'a'],['input'=>"a\naa",'out'=>'']],
'tags'=>'strings,sliding-window','companies'=>'amazon,google,meta,microsoft'],

['title'=>'Sliding Window Maximum','slug'=>'sliding-window-maximum','difficulty'=>'Hard',
'desc'=>'Return max of each sliding window of size k.

Input: line 1 = array (space-separated), line 2 = k.
Output: maximums space-separated.',
'examples'=>'Input:
1 3 -1 -3 5 3 6 7
3
Output: 3 3 5 5 6 7',
'constraints'=>'1 <= n <= 10^5, -10^4 <= nums[i] <= 10^4, 1 <= k <= n',
'tests'=>[['input'=>"1 3 -1 -3 5 3 6 7\n3",'out'=>'3 3 5 5 6 7'],['input'=>"1\n1",'out'=>'1'],['input'=>"1 -1\n1",'out'=>'1 -1']],
'tags'=>'arrays,sliding-window','companies'=>'amazon,google,meta,microsoft'],

['title'=>'Largest Rectangle in Histogram','slug'=>'largest-rectangle-histogram','difficulty'=>'Hard',
'desc'=>'Find the area of the largest rectangle in a histogram.

Input: space-separated heights.
Output: largest area.',
'examples'=>'Input: 2 1 5 6 2 3
Output: 10',
'constraints'=>'1 <= n <= 10^5, 0 <= heights[i] <= 10^4',
'tests'=>[['input'=>'2 1 5 6 2 3','out'=>'10'],['input'=>'2 4','out'=>'4'],['input'=>'1 1 1 1 1','out'=>'5']],
'tags'=>'arrays,stack','companies'=>'amazon,google,meta'],

['title'=>'Regular Expression Matching','slug'=>'regex-matching','difficulty'=>'Hard',
'desc'=>'Implement regular expression matching with . and *. . matches any single character, * matches zero or more of the preceding element.

Input: line 1 = s, line 2 = p.
Output: true or false.',
'examples'=>'Input:
aa
a*
Output: true',
'constraints'=>'1 <= s.length <= 20, 1 <= p.length <= 30. s has only lowercase letters. p has lowercase letters, . and *.',
'tests'=>[['input'=>"aa\na*",'out'=>'true'],['input'=>"mississippi\nmis*is*p*.",'out'=>'false'],['input'=>"aab\nc*a*b",'out'=>'true']],
'tags'=>'strings,dynamic-programming','companies'=>'amazon,google,meta,microsoft'],

['title'=>'Word Ladder','slug'=>'word-ladder','difficulty'=>'Hard',
'desc'=>'Find the shortest transformation sequence length from beginWord to endWord, changing one letter at a time (each intermediate word must be in wordList). Return 0 if impossible.

Input: line 1 = beginWord, line 2 = endWord, line 3 = wordList (space-separated).
Output: sequence length (includes begin and end).',
'examples'=>'Input:
hit
cog
hot dot dog lot log cog
Output: 5',
'constraints'=>'1 <= wordList.length <= 5000, all words same length, lowercase.',
'tests'=>[['input'=>"hit\ncog\nhot dot dog lot log cog",'out'=>'5'],['input'=>"hit\ncog\nhot dot dog lot log",'out'=>'0'],['input'=>"a\nc\na b c",'out'=>'2']],
'tags'=>'graphs,bfs','companies'=>'amazon,google,microsoft'],

['title'=>'Median of Two Sorted Arrays','slug'=>'median-two-sorted-arrays','difficulty'=>'Hard',
'desc'=>'Find the median of two sorted arrays in O(log(m+n)) time.

Input: line 1 = first sorted array (space-separated, or empty line), line 2 = second sorted array (space-separated).
Output: median (floating point, 1 decimal place).',
'examples'=>'Input:
1 3
2
Output: 2.0',
'constraints'=>'0 <= m, n <= 1000',
'tests'=>[['input'=>"1 3\n2",'out'=>'2.0'],['input'=>"1 2\n3 4",'out'=>'2.5'],['input'=>"\n1",'out'=>'1.0']],
'tags'=>'arrays,binary-search','companies'=>'amazon,google,meta,microsoft,apple'],

['title'=>'Longest Valid Parentheses','slug'=>'longest-valid-parentheses','difficulty'=>'Hard',
'desc'=>'Find the length of the longest valid parentheses substring.

Input: a string of ( and ).
Output: length.',
'examples'=>'Input: (()
Output: 2',
'constraints'=>'0 <= s.length <= 3 * 10^4. Only ( and ).',
'tests'=>[['input'=>'(()','out'=>'2'],['input'=>')()())','out'=>'4'],['input'=>'','out'=>'0']],
'tags'=>'strings,stack,dynamic-programming','companies'=>'amazon,google,meta'],

['title'=>'Jump Game II','slug'=>'jump-game-ii','difficulty'=>'Hard',
'desc'=>'Find the minimum number of jumps to reach the last index.

Input: space-separated integers (max jump lengths).
Output: minimum jumps.',
'examples'=>'Input: 2 3 1 1 4
Output: 2',
'constraints'=>'1 <= n <= 10^4, 0 <= nums[i] <= 1000. Guaranteed reachable.',
'tests'=>[['input'=>'2 3 1 1 4','out'=>'2'],['input'=>'2 3 0 1 4','out'=>'2'],['input'=>'1 2','out'=>'1']],
'tags'=>'arrays,greedy','companies'=>'amazon,google,meta'],

['title'=>'Gas Station','slug'=>'gas-station','difficulty'=>'Hard',
'desc'=>'Find the starting gas station index to complete the circular route, or -1 if impossible.

Input: line 1 = gas amounts (space-separated), line 2 = costs (space-separated).
Output: starting index or -1.',
'examples'=>'Input:
1 2 3 4 5
3 4 5 1 2
Output: 3',
'constraints'=>'n >= 1, gas[i] >= 0, cost[i] >= 0. Unique answer guaranteed.',
'tests'=>[['input'=>"1 2 3 4 5\n3 4 5 1 2",'out'=>'3'],['input'=>"2 3 4\n3 4 3",'out'=>'-1'],['input'=>"5\n4",'out'=>'0']],
'tags'=>'arrays,greedy','companies'=>'amazon,google,meta'],

['title'=>'Candy','slug'=>'candy','difficulty'=>'Hard',
'desc'=>'Each child gets at least 1 candy. Children with higher rating than neighbors get more. Return minimum total candies.

Input: space-separated ratings.
Output: minimum total candies.',
'examples'=>'Input: 1 0 2
Output: 5',
'constraints'=>'n >= 1, 0 <= ratings[i] <= 2 * 10^4',
'tests'=>[['input'=>'1 0 2','out'=>'5'],['input'=>'1 2 2','out'=>'4'],['input'=>'1 3 2 2 1','out'=>'7']],
'tags'=>'arrays,greedy','companies'=>'amazon,google'],

['title'=>'Wildcard Matching','slug'=>'wildcard-matching','difficulty'=>'Hard',
'desc'=>'Implement wildcard matching with ? (any single character) and * (any sequence, including empty).

Input: line 1 = s, line 2 = p.
Output: true or false.',
'examples'=>'Input:
adceb
*a*b
Output: true',
'constraints'=>'0 <= s.length <= 2000, 0 <= p.length <= 2000. Lowercase letters, ?, *.',
'tests'=>[['input'=>"aa\n*",'out'=>'true'],['input'=>"cb\n?a",'out'=>'false'],['input'=>"adceb\n*a*b",'out'=>'true']],
'tags'=>'strings,dynamic-programming','companies'=>'amazon,google,meta,microsoft'],

['title'=>'Longest Increasing Path in Matrix','slug'=>'longest-increasing-path-matrix','difficulty'=>'Hard',
'desc'=>'Find the length of the longest increasing path in a matrix (move up, down, left, right).

Input: line 1 = m and n, then m lines of n space-separated integers.
Output: length of longest increasing path.',
'examples'=>'Input:
3 3
9 9 4
6 6 8
2 1 1
Output: 4',
'constraints'=>'1 <= m, n <= 200, 0 <= matrix[i][j] <= 2^31-1',
'tests'=>[['input'=>"3 3\n9 9 4\n6 6 8\n2 1 1",'out'=>'4'],['input'=>"3 3\n3 4 5\n3 2 6\n2 2 1",'out'=>'4'],['input'=>"1 1\n1",'out'=>'1']],
'tags'=>'graphs,dynamic-programming','companies'=>'amazon,google,meta'],

['title'=>'Burst Balloons','slug'=>'burst-balloons','difficulty'=>'Hard',
'desc'=>'Burst balloons to maximize coins. Bursting balloon i earns nums[i-1]*nums[i]*nums[i+1] (boundaries treated as 1).

Input: space-separated integers.
Output: maximum coins.',
'examples'=>'Input: 3 1 5 8
Output: 167',
'constraints'=>'1 <= n <= 300, 0 <= nums[i] <= 100',
'tests'=>[['input'=>'3 1 5 8','out'=>'167'],['input'=>'1 5','out'=>'10'],['input'=>'1','out'=>'1']],
'tags'=>'arrays,dynamic-programming','companies'=>'amazon,google'],

['title'=>'Minimum Number of Refueling Stops','slug'=>'min-refueling-stops','difficulty'=>'Hard',
'desc'=>'Find the minimum number of refueling stops to reach target. Return -1 if impossible.

Input: line 1 = target and startFuel space-separated, line 2 = n (number of stations), then n lines "position fuel".
Output: minimum stops or -1.',
'examples'=>'Input:
100 10
4
10 60
20 30
30 30
60 40
Output: 2',
'constraints'=>'1 <= target, startFuel <= 10^9',
'tests'=>[['input'=>"100 10\n4\n10 60\n20 30\n30 30\n60 40",'out'=>'2'],['input'=>"100 1\n0\n",'out'=>'-1'],['input'=>"1 1\n0\n",'out'=>'0']],
'tags'=>'arrays,dynamic-programming,heap','companies'=>'amazon,google'],

['title'=>'Russian Doll Envelopes','slug'=>'russian-doll-envelopes','difficulty'=>'Hard',
'desc'=>'Count the maximum number of envelopes you can nest (both width and height strictly increasing). Each line is "w h".

Input: line 1 = n, then n lines "w h".
Output: maximum nesting count.',
'examples'=>'Input:
4
5 4
6 4
6 7
2 3
Output: 3',
'constraints'=>'1 <= n <= 10^5, 1 <= w, h <= 10^5',
'tests'=>[['input'=>"4\n5 4\n6 4\n6 7\n2 3",'out'=>'3'],['input'=>"1\n1 1",'out'=>'1'],['input'=>"3\n2 3\n2 3\n2 3",'out'=>'1']],
'tags'=>'arrays,dynamic-programming,binary-search','companies'=>'amazon,google'],

];

$inserted = 0;
$skipped  = 0;

foreach ($problems as $p) {
    $tc = json_encode(array_map(fn($t) => [
        'input'           => $t['input'],
        'expected_output' => $t['out'],
    ], $p['tests']));

    $stmt = $pdo->prepare("
        INSERT IGNORE INTO problems
          (title, slug, difficulty, description, examples, constraints,
           test_cases, tags, companies, is_public)
        VALUES (?,?,?,?,?,?,?,?,?,1)
    ");
    $rows = $stmt->execute([
        $p['title'], $p['slug'], $p['difficulty'],
        $p['desc'], $p['examples'], $p['constraints'],
        $tc, $p['tags'], $p['companies'],
    ]);
    if ($stmt->rowCount() > 0) $inserted++;
    else $skipped++;
}

echo "Done. Inserted: $inserted | Skipped (duplicates): $skipped\n";
echo "Total problems now: " . $pdo->query("SELECT COUNT(*) FROM problems")->fetchColumn() . "\n";
