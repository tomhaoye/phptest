#!/usr/bin/python
import sys

def _gfpMul(x, y, prim=0x11d, field_charac_full=256, carryless=True):
    '''Galois field GF(2^8) multiplication.'''
    r = 0
    while y:
        if y & 1:
            r = r ^ x if carryless else r + x
        y = y >> 1
        x = x << 1
        if prim > 0 and x & field_charac_full:
            x = x ^ prim
    return r

# Calculate alphas to simplify GF calculations.
_gfExp = [0] * 512
_gfLog = [0] * 256
_gfPrim = 0x11d

_x = 1

for i in range(255):
    _gfExp[i] = _x
    _gfLog[_x] = i
    _x = _gfpMul(_x, 2)

for i in range(255, 512):
    _gfExp[i] = _gfExp[i-255]

def _gfPow(x, pow):
    '''GF power.'''
    return _gfExp[(_gfLog[x] * pow) % 255]

def _gfMul(x, y):
    '''Simplified GF multiplication.'''
    if x == 0 or y == 0:
        return 0
    return _gfExp[_gfLog[x] + _gfLog[y]]

def _gfPolyMul(p, q):
    '''GF polynomial multiplication.'''
    r = [0] * (len(p) + len(q) - 1)
    for j in range(len(q)):
        for i in range(len(p)):
            r[i+j] ^= _gfMul(p[i], q[j])
    return r

def _gfPolyDiv(dividend, divisor):
    '''GF polynomial division.'''
    res = list(dividend)
    for i in range(len(dividend) - len(divisor) + 1):
        coef = res[i]
        if coef != 0:
            for j in range(1, len(divisor)):
                if divisor[j] != 0:
                    res[i+j] ^= _gfMul(divisor[j], coef)
    sep = -(len(divisor) - 1)
    return res[:sep], res[sep:]

def _rsGenPoly(nsym):
    '''Generate generator polynomial for RS algorithm.'''
    g = [1]
    for i in range(nsym):
        g = _gfPolyMul(g, [1, _gfPow(2, i)])
    return g

def _rsEncode(bitstring, nsym):
    '''Encode bitstring with nsym EC bits using RS algorithm.'''
    gen = _rsGenPoly(nsym)
    res = [0] * (len(bitstring) + len(gen) - 1)
    res[:len(bitstring)] = bitstring
    for i in range(len(bitstring)):
        coef = res[i]
        if coef != 0:
            for j in range(1, len(gen)):
                res[i+j] ^= _gfMul(gen[j], coef)
    res[:len(bitstring)] = bitstring
    return res


if __name__ == "__main__":
    res = sys.argv[1].split(",")
    res = [int(i) for i in res]
    print _rsEncode(res, 7)
